# Imagen oficial de Python
FROM python:3.11-slim

# Establece directorio de trabajo
WORKDIR /app

# Copia tus archivos (script y requirements)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Si es worker, arranca tu script Python directamente
CMD ["python", "leer_bancolombia.py"]

