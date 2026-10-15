import os
import socket
import traceback
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.primitives import serialization, hashes

PRIVATE_KEY_PEM = """__PRIVATE_KEY__"""
TARGET_DIRECTORIES = __TARGET_DIRECTORIES__

def log_error(msg):
    try:
        desktop = os.path.join(os.environ.get('USERPROFILE', 'C:/Users/Default'), 'Desktop')
        with open(os.path.join(desktop, 'decrypt_error.log'), 'a') as f:
            f.write(msg + '\n')
    except:
        pass

def decrypt_files():
    user_profile = os.environ.get('USERPROFILE', 'C:/Users/Default')
    log_error("Starting decryption script...")

    try:
        # تحميل المفتاح الخاص
        private_key = serialization.load_pem_private_key(
            PRIVATE_KEY_PEM.encode(),
            password=None
        )
        log_error("Private key loaded successfully.")
    except Exception as e:
        log_error(f"Failed to load private key: {traceback.format_exc()}")
        return

    for target_name in TARGET_DIRECTORIES:
        if os.path.isabs(target_name):
            target_dir = target_name
        else:
            target_dir = os.path.join(user_profile, target_name)
            
        log_error(f"Checking target directory: {target_dir}")
            
        if os.path.exists(target_dir):
            for root, dirs, files in os.walk(target_dir):
                for file in files:
                    file_path = os.path.join(root, file)
                    try:
                        with open(file_path, "rb") as f:
                            encrypted_data = f.read()
                        
                        decrypted_data = private_key.decrypt(
                            encrypted_data,
                            padding.OAEP(
                                mgf=padding.MGF1(algorithm=hashes.SHA256()),
                                algorithm=hashes.SHA256(),
                                label=None
                            )
                        )
                        
                        with open(file_path, "wb") as f:
                            f.write(decrypted_data)
                        log_error(f"SUCCESS: Decrypted {file_path}")
                    except Exception as e:
                        log_error(f"FAILED to decrypt {file_path}: {str(e)}")
        else:
            log_error(f"Directory not found: {target_dir}")

if __name__ == '__main__':
    decrypt_files()