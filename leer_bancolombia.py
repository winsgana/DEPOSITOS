#!/usr/bin/env python3
import os, imaplib, email, re, requests, logging, time
from email.header import decode_header

# optional bs4 for html->text fallback
try:
    from bs4 import BeautifulSoup
    HAVE_BS = True
except Exception:
    HAVE_BS = False

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
log = logging.getLogger("leer_bancolombia")

IMAP_SERVER = os.environ.get('IMAP_SERVER', 'imap.gmail.com')
IMAP_USER = os.environ.get('IMAP_USER')
IMAP_PASS = os.environ.get('IMAP_PASS')
FROM_EMAIL = os.environ.get('FROM_EMAIL', 'alertasynotificaciones@notificacionesbancolombia.com')
GOOGLE_SHEET_URL = os.environ.get('GOOGLE_SHEET_URL')

# Poll behavior: 0 = run once, >0 = loop seconds
POLL_INTERVAL = int(os.environ.get('POLL_INTERVAL', '0'))

if not IMAP_USER or not IMAP_PASS or not GOOGLE_SHEET_URL:
    log.error("Faltan variables de entorno: IMAP_USER / IMAP_PASS / GOOGLE_SHEET_URL")
    raise SystemExit(1)

# Regex (ajusta si varía el formato)
re_monto = re.compile(r'Recibiste\s*\$([\d.,]+)', re.IGNORECASE)
re_nombre = re.compile(r'por QR de (.*?) en tu cuenta', re.IGNORECASE)
re_fecha = re.compile(r'el\s+(\d{4}/\d{2}/\d{2})', re.IGNORECASE)
re_hora = re.compile(r'a las\s+(\d{1,2}:\d{2})', re.IGNORECASE)

def get_text_from_msg(msg):
    """Extrae texto del mensaje: prioriza text/plain, si no hay usa text/html (con BeautifulSoup si está)"""
    # multipart
    if msg.is_multipart():
        # prefer text/plain
        for part in msg.walk():
            ctype = part.get_content_type()
            disp = str(part.get('Content-Disposition') or '')
            if ctype == 'text/plain' and 'attachment' not in disp:
                try:
                    charset = part.get_content_charset() or 'utf-8'
                    return part.get_payload(decode=True).decode(charset, errors='ignore')
                except Exception:
                    pass
        # try html
        for part in msg.walk():
            if part.get_content_type() == 'text/html':
                try:
                    charset = part.get_content_charset() or 'utf-8'
                    html = part.get_payload(decode=True).decode(charset, errors='ignore')
                    if HAVE_BS:
                        return BeautifulSoup(html, 'lxml').get_text("\n")
                    else:
                        # fallback simple strip tags
                        return re.sub('<[^<]+?>', '', html)
                except Exception:
                    pass
    else:
        ctype = msg.get_content_type()
        payload = msg.get_payload(decode=True)
        if not payload:
            return ""
        try:
            charset = msg.get_content_charset() or 'utf-8'
            text = payload.decode(charset, errors='ignore')
        except Exception:
            try:
                text = payload.decode(errors='ignore')
            except:
                text = str(payload)
        if ctype == 'text/html':
            if HAVE_BS:
                return BeautifulSoup(text, 'lxml').get_text("\n")
            else:
                return re.sub('<[^<]+?>', '', text)
        return text

def add_to_sheet(monto, nombre, fecha, hora):
    payload = {"monto": monto, "nombre": nombre, "fecha": fecha, "hora": hora}
    try:
        r = requests.post(GOOGLE_SHEET_URL, json=payload, timeout=15)
        log.info("POST -> status=%s text=%s", r.status_code, r.text.strip())
        return r.status_code == 200 and r.text.strip().upper().startswith("OK")
    except Exception as e:
        log.exception("Error POST a Apps Script: %s", e)
        return False

def process_once():
    log.info("Conectando a IMAP %s (usuario %s)", IMAP_SERVER, IMAP_USER)
    try:
        mail = imaplib.IMAP4_SSL(IMAP_SERVER)
        mail.login(IMAP_USER, IMAP_PASS)
    except Exception as e:
        log.exception("Error conectando a IMAP: %s", e)
        return

    try:
        mail.select("INBOX")
        status, data = mail.search(None, f'(UNSEEN FROM "{FROM_EMAIL}")')
        if status != "OK":
            log.warning("IMAP search returned status=%s data=%s", status, data)
            mail.logout()
            return

        ids = data[0].split()
        log.info("Mensajes UNSEEN encontrados: %d", len(ids))
        if not ids:
            mail.logout()
            return

        for num in ids:
            try:
                log.info("Procesando id=%s", num.decode() if isinstance(num, bytes) else str(num))
                status, msg_data = mail.fetch(num, '(RFC822)')
                if status != "OK":
                    log.warning("fetch returned %s for %s", status, num); continue
                raw = msg_data[0][1]
                msg = email.message_from_bytes(raw)

                # obtener body
                body = get_text_from_msg(msg) or ""
                preview = (body[:300] + '...') if len(body) > 300 else body
                log.info("Body preview: %s", preview.replace("\n"," ")[:300])

                # extraer campos
                monto_m = re_monto.search(body)
                nombre_m = re_nombre.search(body)
                fecha_m = re_fecha.search(body)
                hora_m = re_hora.search(body)

                monto = monto_m.group(1).strip() if monto_m else ''
                nombre = nombre_m.group(1).strip() if nombre_m else ''
                fecha = fecha_m.group(1).strip() if fecha_m else ''
                hora = hora_m.group(1).strip() if hora_m else ''

                log.info("Extraído -> monto=%r nombre=%r fecha=%r hora=%r", monto, nombre, fecha, hora)

                if not monto or not nombre:
                    log.warning("Datos incompletos (monto/nombre faltante). No se enviará. Marcar como leído para evitar bucle.")
                    # marcar como leído para no procesarlo una y otra vez
                    mail.store(num, '+FLAGS', '\\Seen')
                    continue

                success = add_to_sheet(monto, nombre, fecha, hora)
                if success:
                    log.info("Insertado correctamente en Sheets. Marcando como leído.")
                    mail.store(num, '+FLAGS', '\\Seen')
                else:
                    log.warning("No se marcó como leído porque POST no devolvió OK")

            except Exception as e:
                log.exception("Error procesando mensaje %s: %s", num, e)
                # no marcar como leído para poder reintentarlo
        mail.logout()
    except Exception as e:
        log.exception("Error general en procesamiento: %s", e)
        try: mail.logout()
        except: pass

def main():
    if POLL_INTERVAL > 0:
        while True:
            process_once()
            time.sleep(POLL_INTERVAL)
    else:
        process_once()

if __name__ == "__main__":
    main()
