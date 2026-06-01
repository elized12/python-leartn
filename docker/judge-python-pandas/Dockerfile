FROM python:3.12-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends time ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir \
    numpy \
    openpyxl \
    pandas \
    pyarrow

RUN useradd --create-home --shell /usr/sbin/nologin judge

WORKDIR /workspace

USER judge

CMD ["python3", "-c", "import pandas; print(pandas.__version__)"]
