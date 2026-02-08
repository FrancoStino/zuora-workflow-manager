# LarAgent Monitoring Setup

## Overview

Questo documento descrive il sistema di monitoring implementato per la migrazione da neuron-ai a laragent, includendo configurazione log, metriche di osservabilità, e piano di monitoraggio a 48 ore.

**Data Setup**: 8 Febbraio 2026  
**Sistema**: LarAgent v1.2 con DataAnalystAgent  
**Monitoring Period**: 48 ore continuative prima del cleanup finale

---

## 1. Log Configuration

### Log Channel Dedicato

Configurato canale log separato per laragent in `config/logging.php`:

```php
'laragent' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laragent.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 14,  // Retention: 14 giorni
    'replace_placeholders' => true,
],
```

**Benefici:**
- Separazione log AI da application log
- Retention automatica (14 giorni)
- Daily rotation per gestione dimensione file
- Debug level per catturare tutti eventi durante monitoring period

### Hook di Monitoring

Implementato `afterToolExecution` hook in `DataAnalystAgentLaragent`:

```php
$this->afterToolExecution(function ($tool, $result) {
    $toolName = is_string($tool) ? $tool : (method_exists($tool, 'getName') ? $tool->getName() : 'unknown');
    
    Log::channel('laragent')->info('LarAgent Tool Executed', [
        'tool' => $toolName,
        'success' => !is_null($result),
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

**Metriche Catturate:**
- Nome tool eseguito (getDatabaseSchema, executeQuery)
- Success status (null check per failure detection)
- Timestamp ISO8601 per analisi temporale

---

## 2. Security Monitoring

### Global Query Listener

Layer di sicurezza globale in `AppServiceProvider::boot()`:

```php
DB::listen(function (QueryExecuted $query) {
    $enableSecurityListener = config('app.enable_ai_security_listener', true);
    
    if (!$enableSecurityListener) {
        return;
    }

    if (preg_match('/\b(INSERT|UPDATE|DELETE)\b/i', $query->sql)) {
        Log::critical('SECURITY BREACH: AI attempted write', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
        ]);

        throw new \RuntimeException('AI write operations forbidden');
    }
});
```

**Eventi Monitorati:**
- Tutti tentativi di write operations (INSERT, UPDATE, DELETE)
- Log CRITICAL per immediate alert
- Exception throwing per prevenire execution

---

## 3. 48-Hour Monitoring Plan

### Obiettivi

- ✅ **Zero Regressioni**: Nessun errore AI generato
- ✅ **Performance Stability**: Latency entro +20% baseline
- ✅ **Security Compliance**: Nessuna violazione security listener
- ✅ **Data Integrity**: Storico chat completo e accessibile

### Checklist Monitoring (ogni 2 ore)

```bash
# 1. Verifica error rate
tail -100 storage/logs/laragent.log | grep -i "error\|exception\|fail" | wc -l
# Assert: 0 errori

# 2. Verifica tool execution success rate
grep "LarAgent Tool Executed" storage/logs/laragent.log | grep -c "success\":true"
grep "LarAgent Tool Executed" storage/logs/laragent.log | grep -c "success\":false"
# Assert: success rate > 95%

# 3. Verifica security violations
grep "SECURITY BREACH" storage/logs/laravel.log
# Assert: Nessuna occorrenza

# 4. Verifica response times (sample)
# Manuale: test query "Quanti task ci sono?" e verifica < 3 secondi

# 5. Verifica storico chat integrità
lando artisan tinker --execute="echo ChatMessage::count();"
# Assert: Count invariato (baseline dal Task 1)
```

### Metriche da Tracciare

| Metrica | Target | Azione se Superato |
|---------|--------|-------------------|
| Error Rate | < 1% | Investigate logs, rollback se > 5% |
| Tool Execution Time | < 3s (p95) | Performance profiling |
| Security Violations | 0 | Immediate rollback |
| Failed Queries | < 2% | Check query validation logic |
| Chat History Access | 100% | Verify database integrity |

### Log Retention Durante Monitoring

```bash
# Backup logs prima della retention rotation
cp storage/logs/laragent.log storage/logs/laragent-migration-baseline.log
cp storage/logs/laravel.log storage/logs/laravel-migration-baseline.log

# Archiviazione post-monitoring (dopo 48h)
tar -czf storage/logs/migration-monitoring-$(date +%Y%m%d).tar.gz \
    storage/logs/laragent-migration-baseline.log \
    storage/logs/laravel-migration-baseline.log
```

---

## 4. Query Monitoring Commands

### Real-Time Monitoring

```bash
# Stream laragent log in tempo reale
tail -f storage/logs/laragent.log

# Stream con filtro errori
tail -f storage/logs/laragent.log | grep -i "error\|exception"

# Stream security events
tail -f storage/logs/laravel.log | grep -i "security breach"
```

### Analisi Post-Monitoring

```bash
# Tool execution summary
grep "LarAgent Tool Executed" storage/logs/laragent.log | \
    awk -F'"tool":"' '{print $2}' | \
    awk -F'"' '{print $1}' | \
    sort | uniq -c | sort -rn

# Success rate per tool
grep "getDatabaseSchema" storage/logs/laragent.log | grep -c "success\":true"
grep "executeQuery" storage/logs/laragent.log | grep -c "success\":true"

# Errori aggregati
grep "ERROR" storage/logs/laragent.log | \
    awk '{print $0}' | \
    sort | uniq -c | sort -rn
```

---

## 5. Rollback Procedure (Se Necessario)

Se durante le 48 ore si verificano problemi critici:

### Trigger Conditions per Rollback

- Security violations > 0
- Error rate > 5%
- Performance degradation > 50% baseline
- Data integrity issues (chat history corruption)

### Rollback Steps

```bash
# 1. Switch feature flag
sed -i 's/AI_PROVIDER=laragent/AI_PROVIDER=neuron/' .env
lando artisan config:clear

# 2. Verifica service binding
lando artisan tinker --execute="echo app(App\\Services\\NeuronChatService::class)::class;"
# Assert: Output "NeuronChatService"

# 3. Restore database backup (solo se necessario)
lando mysql zuora_workflows < storage/backups/pre-laragent-migration-*.sql

# 4. Restart queue workers
lando artisan queue:restart

# 5. Verifica funzionalità
lando artisan test --filter=ChatServiceTest
```

---

## 6. Success Criteria

Al termine delle 48 ore, considerare monitoring PASSED se:

- ✅ Zero security violations
- ✅ Error rate < 1%
- ✅ Performance entro +20% baseline
- ✅ All golden queries passing
- ✅ Chat history 100% accessibile
- ✅ Nessun complaint utenti

**Azione Post-Success:**
- Procedere con cleanup (Task 14 finale)
- Archiviare logs monitoring
- Aggiornare knowledge base con lessons learned

---

## 7. Troubleshooting

### Issue: High Error Rate

```bash
# Diagnosi
grep "ERROR" storage/logs/laragent.log | tail -20

# Azioni
1. Verificare API provider config (OpenAI API key valida?)
2. Controllare rate limiting (troppi requests?)
3. Validare query generation (syntax errors?)
```

### Issue: Performance Degradation

```bash
# Diagnosi
grep "Tool Executed" storage/logs/laragent.log | \
    awk -F'timestamp' '{print $2}' | head -100

# Azioni
1. Profiling con Telescope (se installato)
2. Verificare database query performance (EXPLAIN)
3. Controllare API latency (OpenAI response times)
```

### Issue: Security Violations

```bash
# Diagnosi IMMEDIATA
grep "SECURITY BREACH" storage/logs/laravel.log

# Azioni URGENTI
1. ROLLBACK immediato (feature flag = neuron)
2. Analisi SQL tentato: grep "sql.*INSERT\|UPDATE\|DELETE"
3. Fix security validation in DataAnalystAgent
4. Re-test con golden queries prima di re-deploy
```

---

## 8. Contatti e Escalation

**Monitoring Owner**: DevOps Team  
**Escalation Path**: Critical issues → Immediate rollback → Post-mortem analysis  
**Documentation**: Tutti findings in `.sisyphus/notepads/laragent-migration/learnings.md`

---

**Next Steps**: Dopo 48h monitoring PASSED → Procedere con cleanup (rimozione neuron-ai)
