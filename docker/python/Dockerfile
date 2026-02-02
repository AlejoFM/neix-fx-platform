FROM python:3.9-slim

WORKDIR /app

# Copy requirements first (para aprovechar cache de Docker)
COPY scripts/price_generator/requirements.txt .

# Install dependencies (solo se ejecuta si requirements.txt cambia)
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code (después de instalar dependencias para mejor cache)
COPY scripts/price_generator/ .

EXPOSE 5000

CMD ["python", "generator.py"]
