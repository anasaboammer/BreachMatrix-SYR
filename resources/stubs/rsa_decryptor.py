import os
import socket
from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.primitives import serialization, hashes

PRIVATE_KEY_PEM = """__PRIVATE_KEY__"""
TARGET_DIRECTORIES = __TARGET_DIRECTORIES__

def decrypt_files():
    user_profile = os.environ.get('USERPROFILE', 'C:/Users/Default')
    
    private_key = serialization.load_pem_private_key(
        PRIVATE_KEY_PEM.encode(),
        password=None
    )
    
    for target_name in TARGET_DIRECTORIES:
        if os.path.isabs(target_name):
            target_dir = target_name
        else:
            target_dir = os.path.join(user_profile, target_name)
            
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
                    except Exception:
                        pass

if __name__ == '__main__':
    decrypt_files()