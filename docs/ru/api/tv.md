# Дополнительные поля (TV)

Template variables MODX только по запросу. Без параметров в JSON их нет.

## Запрос

```
GET /api/v1/resources/5?tv_fields=image,subtitle
```

`tv_fields` — список имён TV через запятую. Неизвестные или запрещённые TV пропускаются.

## Реализация

`ModxTvProvider` читает значения через `modTemplateVar::getValue()` для ID ресурса. Действуют права на поля.

## Производительность

На каждый TV возможен отдельный запрос. На списках держите `tv_fields` коротким. Большие наборы TV удобнее на GET одного ресурса.

## См. также

- [Resources](resources.md)
- [Поля](fields.md)
