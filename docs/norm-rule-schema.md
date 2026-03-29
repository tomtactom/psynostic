# Regel-JSON-Schema für `demographic_rule_json`

Dieses Schema beschreibt **strukturierte Regeln** für Normgruppen. Es werden **keine freien Code-Strings** verwendet.

## 1) Ziel

Für jede Normgruppe (`norm_groups.demographic_rule_json`) wird eine logische Bedingung hinterlegt, die gegen Session-Demografie geprüft wird.

Beispiel-Anforderung:

- `age >= 18 && age <= 29 && gender == "female"`

wird als strukturierte Regel gespeichert (siehe Beispiel unten).

## 2) Knoten-Typen

Jeder Regelknoten hat ein Feld `op`.

### 2.1 Logik-Knoten

- `{"op":"all","conditions":[ ... ]}`: Alle Bedingungen müssen erfüllt sein (AND).
- `{"op":"any","conditions":[ ... ]}`: Mindestens eine Bedingung muss erfüllt sein (OR).
- `{"op":"not","condition":{ ... }}`: Negation einer Bedingung (NOT).

### 2.2 Prädikat-Knoten

```json
{
  "op": "predicate",
  "field": "age",
  "operator": "gte",
  "value": 18
}
```

Felder:

- `field` (string): Demografie-Schlüssel, z. B. `age`, `gender`, `country`.
- `operator` (string): Einer von:
  - `eq`, `neq`
  - `gt`, `gte`, `lt`, `lte`
  - `in` (mit Array-Wert)
  - `between` (mit `[min, max]`)
  - `exists` (prüft nur, ob das Feld vorhanden ist)
- `value`: abhängig vom Operator.

## 3) Beispiel: 18–29 & weiblich

```json
{
  "op": "all",
  "conditions": [
    {"op":"predicate","field":"age","operator":"gte","value":18},
    {"op":"predicate","field":"age","operator":"lte","value":29},
    {"op":"predicate","field":"gender","operator":"eq","value":"female"}
  ]
}
```

## 4) Priorisierung bei mehreren Treffern

Wenn mehrere Normgruppen passen:

1. Höchster **Spezifitäts-Score** gewinnt.
2. Score-Berechnung (Heuristik):
   - `eq` / `in`: +3
   - `between` / `gte` / `lte`: +2
   - andere Prädikate: +1
   - Logik-Knoten addieren rekursiv die Kinder-Scores.
3. Bei Gleichstand: niedrigste `norm_group.id` (deterministisch).

## 5) Fallback-Verhalten

Wenn keine gruppenspezifische Regel passt:

- Fallback auf `norm_groups.is_global_reference = 1` derselben `questionnaire_id`.
- Bericht markieren mit `used_global_fallback = true`.
- Falls keine globale Gruppe existiert, wird `match_status = "no_norm_group"` gesetzt.

## 6) Mapping von `questionnaire_scores` auf `norm_rows`

Für jeden Rohwert (`raw_score`) wird in der gewählten Normgruppe eine passende Zeile gesucht:

- gleicher `questionnaire_id`
- gleicher `scale_key`
- `raw_min <= raw_score <= raw_max`

Wenn keine Zeile passt, wird `match_status = "missing_norm_row"` gesetzt und im Report-Flag `missing_norm_rows` protokolliert.
