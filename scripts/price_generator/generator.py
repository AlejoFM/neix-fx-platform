#!/usr/bin/env python3
"""
Generador de precios FX simulado con volatilidad realista
Simula fluctuaciones de mercado usando Random Walk y volatilidad variable
"""

from flask import Flask, jsonify
from flask_cors import CORS
import random
import math
import time
from datetime import datetime
from collections import deque

app = Flask(__name__)
CORS(app)

# Precios base iniciales
BASE_PRICES = {
    'EUR/USD': 1.1000,
    'ARG/USD': 0.0012,
    'ARG/EUR': 0.0011,
}

# Configuración de volatilidad por instrumento (desviación estándar anual aproximada)
# Valores más altos = más volatilidad
VOLATILITY = {
    'EUR/USD': 0.0008,   # ~8% anual (par estable)
    'ARG/USD': 0.0030,   # ~30% anual (más volátil)
    'ARG/EUR': 0.0025,   # ~25% anual (volatilidad media)
}

# Tendencias actuales (drift) - pueden cambiar con el tiempo
TRENDS = {
    'EUR/USD': 0.0001,   # Tendencia ligeramente alcista
    'ARG/USD': -0.0002,  # Tendencia bajista
    'ARG/EUR': -0.0001,  # Tendencia ligeramente bajista
}

# Historial de precios para suavizado (últimos N valores)
PRICE_HISTORY = {
    'EUR/USD': deque([BASE_PRICES['EUR/USD']], maxlen=5),
    'ARG/USD': deque([BASE_PRICES['ARG/USD']], maxlen=5),
    'ARG/EUR': deque([BASE_PRICES['ARG/EUR']], maxlen=5),
}

# Precios actuales (se actualizan dinámicamente)
current_prices = BASE_PRICES.copy()

# Contador para eventos de alta volatilidad
volatility_events = {
    'EUR/USD': 0,
    'ARG/USD': 0,
    'ARG/EUR': 0,
}

def get_current_volatility(instrument):
    """Obtiene la volatilidad actual, con posibilidad de eventos de alta volatilidad"""
    base_vol = VOLATILITY[instrument]
    
    # Eventos de alta volatilidad (simulando noticias del mercado)
    # Cada ~100 actualizaciones hay un evento de alta volatilidad
    volatility_events[instrument] += 1
    if volatility_events[instrument] > 100:
        volatility_events[instrument] = 0
        # 30% de probabilidad de evento de alta volatilidad
        if random.random() < 0.3:
            return base_vol * random.uniform(2.0, 4.0)  # 2-4x más volátil
    
    # Variación normal de volatilidad (±20%)
    return base_vol * random.uniform(0.8, 1.2)

def generate_price(instrument):
    """
    Genera un nuevo precio usando Random Walk (caminata aleatoria)
    con volatilidad variable y tendencias
    """
    base = current_prices[instrument]
    volatility = get_current_volatility(instrument)
    trend = TRENDS[instrument]
    
    # Random Walk: cambio de precio basado en distribución normal
    # Usando el modelo de Black-Scholes simplificado: dS = S * (μ*dt + σ*dW)
    # donde μ es el drift (tendencia) y σ es la volatilidad
    
    # Generar cambio aleatorio usando distribución normal
    # random.gauss(media, desviación_estándar)
    random_change = random.gauss(0, volatility)
    
    # Aplicar tendencia (drift)
    change = trend + random_change
    
    # Calcular nuevo precio
    new_price = base * (1 + change)
    
    # Prevenir precios negativos o extremos (safety bounds)
    # Limitar cambios a ±5% por actualización
    if abs(new_price - base) / base > 0.05:
        # Si el cambio es muy grande, limitarlo
        direction = 1 if new_price > base else -1
        new_price = base * (1 + direction * 0.05)
    
    # Suavizado: usar media móvil simple para movimientos más naturales
    PRICE_HISTORY[instrument].append(new_price)
    smoothed_price = sum(PRICE_HISTORY[instrument]) / len(PRICE_HISTORY[instrument])
    
    # Actualizar precio actual (70% precio suavizado, 30% precio directo)
    final_price = smoothed_price * 0.7 + new_price * 0.3
    current_prices[instrument] = final_price
    
    # Actualizar tendencia ocasionalmente (cambios de mercado)
    if random.random() < 0.05:  # 5% de probabilidad
        TRENDS[instrument] = random.uniform(-0.0003, 0.0003)
    
    return round(final_price, 6)

@app.route('/prices', methods=['GET'])
def get_prices():
    """Endpoint que retorna los precios actuales de todos los instrumentos"""
    prices = []
    
    for instrument in ['EUR/USD', 'ARG/USD', 'ARG/EUR']:
        price = generate_price(instrument)
        prices.append({
            'instrument': instrument,
            'price': price,
            'timestamp': datetime.now().isoformat()
        })
    
    return jsonify({
        'success': True,
        'prices': prices,
        'timestamp': datetime.now().isoformat()
    })

@app.route('/health', methods=['GET'])
def health():
    """Endpoint de salud para verificar que el servicio está funcionando"""
    return jsonify({
        'status': 'ok',
        'service': 'price-generator',
        'timestamp': datetime.now().isoformat()
    })

if __name__ == '__main__':
    print("Iniciando generador de precios FX...")
    print("Instrumentos: EUR/USD, ARG/USD, ARG/EUR")
    print("Endpoint: http://0.0.0.0:5000/prices")
    app.run(host='0.0.0.0', port=5000, debug=False)
