# Documentazione Mintlify - Deploy Guide

La documentazione di Zuora Workflow Manager è gestita con Mintlify e si trova nella cartella `docs/`.

## 🚀 Quick Start

### 1. Preview Locale

```bash
cd docs
npm install -g mintlify
mintlify dev
```

Visita: http://localhost:3000

### 2. Deploy su Mintlify Cloud

Configura la **Documentation Path** su Mintlify Dashboard:
- **Path**: `docs/`
- **Config file**: `docs/docs.json`

Triggera deploy automatico o manuale dal dashboard.

### 3. Self-Hosted

Vedi: https://mintlify.com/docs/self-hosted

## 📚 Struttura Documentazione

```
docs/
├── docs.json                 # Configurazione principale
├── images/                    # Logo, favicon
├── getting-started/           # 4 guide introduttive
│   ├── introduction.mdx
│   ├── installation.mdx
│   ├── configuration.mdx
│   └── verification.mdx
├── architecture/              # 2 guide architetturali
│   ├── architecture.mdx
│   └── overview.mdx
├── configuration/             # Settings implementation
│   └── settings-implementation.mdx
├── features/                  # 3 funzionalità principali
│   ├── overview.mdx
│   ├── sync.mdx
│   └── workflow-graph.mdx
├── development/               # Contributing guide
│   └── contributing.mdx
└── deployment/                # 2 guide deployment
    ├── overview.mdx
    └── deployment.mdx
```

## 📊 Statistiche

| Metrica | Valore |
|---------|--------|
| **Totale File** | 14 file MDX |
| **Sezioni** | 7 sezioni principali |
| **Diagrafi Mermaid** | 15+ |
| **Code Examples** | 30+ |
| **Componenti Mintlify** | Card, Note, Warning, Tip |

## 📝 Modificare la Documentazione

1. Apri un file `.mdx` in `docs/`
2. Modifica il contenuto
3. Usa componenti Mintlify:
   - `<Note>` - Informazioni
   - `<Warning>` - Avvertenze
   - `<Tip>` - Suggerimenti
   - `<Card>` - Navigazione rapida

### Esempio

```mdx
---
title: Nuova Pagina
description: Descrizione breve
---

# Nuova Pagina

<Note>
  Questa è un'informazione utile.
</Note>

<CardGroup cols={2}>
  <Card title="Pagina 1" icon="rocket" href="/page1" />
  <Card title="Pagina 2" icon="gear" href="/page2" />
</CardGroup>
```

## 🔄 Workflow di Aggiornamento

```bash
# 1. Modifica file
nano docs/features/sync.mdx

# 2. Preview locale
cd docs && mintlify dev

# 3. Verifica nel browser
# http://localhost:3000/features/sync

# 4. Commit e push
git add docs/
git commit -m "Update sync documentation"
git push

# 5. Deploy automatico su Mintlify Cloud
```

## 🎨 Personalizzazione

Modifica `docs/docs.json`:

### Colori

```json
{
  "colors": {
    "primary": "#your-color",
    "light": "#your-light-color",
    "dark": "#your-dark-color"
  }
}
```

### Logo

```json
{
  "logo": {
    "light": "/images/logo.svg",
    "dark": "/images/logo-white.svg"
  }
}
```

### Navigazione

```json
{
  "navigation": [
    {
      "group": "Gruppo",
      "pages": [
        "cartella/nuova-pagina"
      ]
    }
  ]
}
```

## ⚡ Troubleshooting

### Errore: `mintlify not found`

```bash
npm install -g mintlify
```

### Immagini non caricate

Verifica che le immagini siano in `docs/images/`:
```bash
ls -la docs/images/
```

### Navigazione non aggiornata

1. Verifica `docs/docs.json` sintassi
2. Riavvia `mintlify dev`
3. Verifica percorsi relativi corretti

### Deploy su Mintlify Cloud

Se Mintlify non trova la documentazione:
1. Vai su Dashboard → Project Settings
2. Imposta **Documentation Path**: `docs/`
3. Assicurati che `docs/docs.json` esista
4. Triggera nuovo deploy

## 📖 Riferimenti

- **Documentazione Mintlify**: https://mintlify.com/docs
- **Mintlify GitHub**: https://github.com/mintlify/mintlify
- **Componenti Disponibili**: https://mintlify.com/docs/components

## 🤝 Contribuire

Per contribuire alla documentazione:

1. Fork il repository
2. Crea branch feature
3. Modifica i file in `docs/`
4. Test con `mintlify dev`
5. Submit PR

---

**Documentazione Powered by Mintlify** 🚀
**Versione**: 1.4.0 |
**Aggiornata**: 28 Dicembre 2025
