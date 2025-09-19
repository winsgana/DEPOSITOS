import os
import imaplib, email, re, requests

IMAP_SERVER = os.environ['IMAP_SERVER']
IMAP_USER   = os.environ['IMAP_USER']
IMAP_PASS   = os.environ['IMAP_PASS']
GOOGLE_SHEET_URL = os.environ['GOOGLE_SHEET_URL']

FROM_EMAIL  = 'alertasynotificaciones@notificacionesbancolombia.com'

def get_bancolombia_emails():
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

        # Extraer datos
        monto = re.search(r'Recibiste\s*\$([\d.,]+)', body)
        monto = monto.group(1) if monto else ''

        nombre = re.search(r'por QR de (.*?) en tu cuenta', body)
        nombre = nombre.group(1) if nombre else ''

        fecha = re.search(r'el\s+(\d{4}/\d{2}/\d{2})', body)
        fecha = fecha.group(1) if fecha else ''

        hora = re.search(r'a las\s+(\d{1,2}:\d{2})', body)
        hora = hora.group(1) if hora else ''

        # Enviar a Google Sheets
        payload = {
            "monto": monto,
            "nombre": nombre,
            "fecha": fecha,
            "hora": hora
        }
        try:
            r = requests.post(GOOGLE_SHEET_URL, json=payload)
            print("Respuesta Sheets:", r.text)
        except Exception as e:
            print("Error enviando a Sheets:", e)

    mail.logout()

if __name__ == "__main__":
    get_bancolombia_emails()

