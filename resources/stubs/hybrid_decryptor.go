package main

import (
	"crypto/aes"
	"crypto/cipher"
	"crypto/rand"
	"crypto/rsa"
	"crypto/sha256"
	"crypto/x509"
	"encoding/pem"
	"io"
	"os"
	"path/filepath"
	"strings"
)

const PrivateKeyPEM = "__PRIVATE_KEY__"
const TargetDirsString = "__TARGET_DIRECTORIES__"

func decryptRSA(cipherText []byte, privKey *rsa.PrivateKey) ([]byte, error) {
	hash := sha256.New()
	return rsa.DecryptOAEP(hash, rand.Reader, privKey, cipherText, nil)
}

func decryptAES(cipherText []byte, key []byte) ([]byte, error) {
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

func parsePrivateKey(pemString string) (*rsa.PrivateKey, error) {
	block, _ := pem.Decode([]byte(pemString))
	if block == nil {
		return nil, nil
	}
	priv, err := x509.ParsePKCS1PrivateKey(block.Bytes)
	if err != nil {
		key, err8 := x509.ParsePKCS8PrivateKey(block.Bytes)
		if err8 != nil {
			return nil, err
		}
		if pKey, ok := key.(*rsa.PrivateKey); ok {
			return pKey, nil
		}
		return nil, err
	}
	return priv, nil
}

func decryptFiles() {
	userProfile := os.Getenv("USERPROFILE")
	if userProfile == "" {
		userProfile = "C:\\Users\\Default"
	}

	privKey, err := parsePrivateKey(PrivateKeyPEM)
	if err != nil || privKey == nil {
		return
	}

	targetDirectories := strings.Split(TargetDirsString, ",")
	rsaKeySize := 256

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
				if err != nil || len(data) <= rsaKeySize {
					return nil
				}

				encKeyBytes := data[:rsaKeySize]
				encContent := data[rsaKeySize:]

				aesKey, err := decryptRSA(encKeyBytes, privKey)
				if err != nil {
					return nil
				}

				decryptedContent, err := decryptAES(encContent, aesKey)
				if err == nil {
					os.WriteFile(path, decryptedContent, 0644)
				}
				return nil
			})
		}
	}
}

func main() {
	decryptFiles()
}