import requests

url = "https://script.google.com/macros/s/AKfycbwCG3pE49m312CdSmJu3JQlATZwmS07ppUvxtSYIpvV7ciQxz_m9sWxBKJeYikIvdAMBg/exec"

payload = {
    "monto": "12345",
    "nombre": "PRUEBA",
    "fecha": "2025-09-19",
    "hora": "15:30"
}

r = requests.post(url, json=payload)
print(r.status_code, r.text)
