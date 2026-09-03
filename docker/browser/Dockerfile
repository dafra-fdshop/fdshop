FROM mcr.microsoft.com/playwright:v1.55.0-noble

WORKDIR /workspace/tests/browser
COPY tests/browser/package.json ./
RUN npm install --no-audit --no-fund
COPY tests/browser/ ./

ENV CI=1
CMD ["npm", "test"]
