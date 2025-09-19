import os, imaplib, email, re, requests

# ===== CONFIGURACIONES =====
IMAP_SERVER = os.environ.get('IMAP_SERVER', 'imap.gmail.com')  # IMAP de Gmail
IMAP_USER   = os.environ.get('IMAP_USER')   # tu correo
IMAP_PASS   = os.environ.get('IMAP_PASS')   # contraseña de aplicación

FROM_EMAIL  = 'alertasynotificaciones@notificacionesbancolombia.com'
GOOGLE_APPS_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbzzakEuCJwUWZznfP60aKoq6fkUGq-yYWrjFV4IPwf1o3SDFkJGdK5pHQu0adHAfClNyA/exec'

def extraer_datos(body):
    """Extrae monto, nombre, fecha y hora del cuerpo del correo"""
    monto_match = re.search(r'Recibiste\s*\$([\d.,]+)', body)
    monto = monto_match.group(1) if monto_match else ''

    nombre_match = re.search(r'por QR de (.*?) en tu cuenta', body)
    nombre = nombre_match.group(1) if nombre_match else ''

    fecha_match = re.search(r'el\s+(\d{4}/\d{2}/\d{2})', body)
    fecha = fecha_match.group(1) if fecha_match else ''

    hora_match = re.search(r'a las\s+(\d{1,2}:\d{2})', body)
    hora = hora_match.group(1) if hora_match else ''

    return monto, nombre, fecha, hora

def get_bancolombia_emails():
    """Lee correos del banco y los envía a Google Sheets"""
    mail = imaplib.IMAP4_SSL(IMAP_SERVER)
    mail.login(IMAP_USER, IMAP_PASS)
    mail.select("inbox")

    status, data = mail.search(None, f'(FROM "{FROM_EMAIL}")')
    mail_ids = data[0].split()

    for num in mail_ids[-5:]:  # últimos 5 correos
        status, data = mail.fetch(num, '(RFC822)')
        raw_email = data[0][1]
        msg = email.message_from_bytes(raw_email)

        body = ""
        if msg.is_multipart():
            for part in msg.walk():
                if part.get_content_type() == 'text/plain':
                    body += part.get_payload(decode=True).decode(errors='ignore')
        else:
            body = msg.get_payload(decode=True).decode(errors='ignore')

        monto, nombre, fecha, hora = extraer_datos(body)

        if monto and nombre:
            payload = {
                "monto": monto,
                "nombre": nombre,
                "fecha": fecha,
                "hora": hora
            }
            r = requests.post(GOOGLE_APPS_SCRIPT_URL, json=payload)
            print(f"Enviado a Sheets: {payload} - Respuesta: {r.text}")

    mail.logout()

if __name__ == "__main__":
    get_bancolombia_emails()
