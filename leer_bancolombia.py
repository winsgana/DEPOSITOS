import os, imaplib, email, re, requests

IMAP_SERVER = os.environ['IMAP_SERVER']
IMAP_USER = os.environ['IMAP_USER']
IMAP_PASS = os.environ['IMAP_PASS']
FROM_EMAIL = os.environ['FROM_EMAIL']
GOOGLE_SHEET_URL = os.environ['GOOGLE_SHEET_URL']

# Regex
re_monto = re.compile(r'Recibiste\s*\$([\d.,]+)')
re_nombre = re.compile(r'por QR de (.*?) en tu cuenta')
re_fecha = re.compile(r'el\s+(\d{4}/\d{2}/\d{2})')
re_hora = re.compile(r'a las\s+(\d{2}:\d{2})')

def add_to_sheet(monto, nombre, fecha, hora):
    data = {
        "monto": monto,
        "nombre": nombre,
        "fecha": fecha,
        "hora": hora
    }
    r = requests.post(GOOGLE_SHEET_URL, json=data)
    print(r.text)  # para ver respuesta del Apps Script

def main():
    mail = imaplib.IMAP4_SSL(IMAP_SERVER)
    mail.login(IMAP_USER, IMAP_PASS)
    mail.select("inbox")

    status, data = mail.search(None, f'(UNSEEN FROM "{FROM_EMAIL}")')
    for num in data[0].split():
        status, msg_data = mail.fetch(num, '(RFC822)')
        msg = email.message_from_bytes(msg_data[0][1])

        if msg.is_multipart():
            for part in msg.walk():
                if part.get_content_type() == "text/plain":
                    body = part.get_payload(decode=True).decode()
                    break
        else:
            body = msg.get_payload(decode=True).decode()

        monto = re_monto.search(body)
        nombre = re_nombre.search(body)
        fecha = re_fecha.search(body)
        hora = re_hora.search(body)

        monto = monto.group(1) if monto else ''
        nombre = nombre.group(1) if nombre else ''
        fecha = fecha.group(1) if fecha else ''
        hora = hora.group(1) if hora else ''

        # enviar directamente a la hoja
        add_to_sheet(monto, nombre, fecha, hora)

        # marcar leído
        mail.store(num, '+FLAGS', '\\Seen')

    mail.logout()

if __name__ == "__main__":
    main()

