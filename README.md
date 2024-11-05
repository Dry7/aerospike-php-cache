Возможный проблемы:

1. 100+ параллельных GET запросов в ключ без кэша. Решение: Дополнительная блокировка в кэше, должен выполниться только первый запрос, остальные упадут с ошибкой. Не будет 100+ запросов к базе.
2. GET с ключем без кэша, sleep(1min) -> попытка сокранить ключ в кэш. Решение: Т.к. используется POST запрос, то если кто-то уже обновил кэш, то процесс упадет.
3. 100+ параллельных POST запросов с обновлением баланса. Решение: Блокировка в кэше, выполнится первый запрос. которому удалось взять блокировку.
4. Блокировка в кэше взята, но процесс упал. Решение: Блокировка берется c ttl, она автоматичеси снимется через некоторое время.
5. Блокировка в кэше взята, но процесс вытеснен другими процессами. Решение: Проверка времени, которое прошло со взятие блокировки. Если прошло больше N, то падаем.

```
Как будет решена проблема конкурентного доступа при обновлении баланса
```
Блокировкой в кэше с TTL

```
Как организовано взаимодействие между PostgreSQL и Aerospike.
```
1. При GET запросе ищем в кэше, если не нашли, во ставим блокировку и идем в БД. Сохраняем в кэш и снимаем блокировку.
2. При POST запросе ищем в кэше, если баланс не изменился, то просто возвращаем 200 ответ. Если изменилось, то ставим блокировку в кэше с ttl и сохраняем в БД и кэш новые значения.

```
Как будет обеспечиваться согласованность кэша и базы данных.
```
Через блокировки и 500 ошибки в случае проблем.

```
Предложите меры оптимизации для повышения производительности и обеспечения актуальности данных в кэше.
```

1. Если данных в БД не очень много мы можем в фоновом процессе перенести всю БД в кэш.
2. Если достаточна консистентность в конечном итоге, то можно ослабить блокировки.
3. Прошрев кэша при проблемах с Aerospike
4. Добавление серверов Aerospike
5. Шардирование PostgreSQL

Cases:

### Write DB first

```
Begin
  <- exit
  SELECT FOR UPDATE // skip
  Save to DB // skip
  Save to cache // skip
Commit (end row lock)
DB+, Cache+
```

```
Begin
  SELECT FOR UPDATE <- error (end transaction)
  Save to DB // skip
  Save to cache // skip
Commit
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  SELECT FOR UPDATE 
  <- error (end transaction and locks)
  Save to DB // skip
  Save to cache // skip
Commit
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to DB  <- error (Rollback db, end row locks)
  Save to cache // skip
Commit
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to DB
  <- exit (Rollback db, end row lock)
  Save to cache // skip
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to DB
  Save to cache <- exit (Rollback db, end row lock)
Commit (end row lock)
DB+, Cache-, Future reads and updates blocked
Must rollback cache!
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to DB
  Save to cache
  <- exit (DB rollback, but cache invalid)
Commit (end row lock)
DB+, Cache-, Future updates blocked
Must rollback cache!
```

5 ok / 2 bad

---------------------------------


### Write cache first

```
Begin
  <- exit
  SELECT FOR UPDATE // skip
  Save to cache // skip
  Save to DB (start row lock) // skip
Commit (end row lock)
DB+, Cache+
```

```
Begin
  SELECT FOR UPDATE <- exit
  Save to cache // skip
  Save to DB (start row lock) // skip
Commit (end row lock)
DB+, Cache+
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
   <- exit
  Save to cache // skip
  Save to DB (start row lock) // skip
Commit (end row lock)
DB+, Cache+
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to cache <- exit
  Save to DB (start row lock) // skip
Commit (end row lock)
DB+, Cache?
Must update cache (old value)
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to cache
  <- exit (Rollback db, end row locks)
  Save to DB (start row lock) // skip
Commit (end row lock)
DB+, Cache-
Must rollback cache!
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to cache
  Save to DB (start row lock) <- exit (Rollback db, end row lock)
Commit (end row lock)
DB+, Cache-
Must rollback cache!
```

```
Begin
  SELECT FOR UPDATE (start row lock for update and read)
  Save to cache
  Save to DB (start row lock)
  <- exit (DB rollback, but cache invalid)
Commit (end row lock)
DB+, Cache-
Must rollback cache!
```

3 ok / 4 bad


------------------------------------------

### With cache lock

```
Begin
  <- exit
  Cache lock (with ttl) // skip
  Save to DB // skip
  Save to cache // skip
Commit (end row lock)
Delete cache lock
DB+, Cache+
```

```
Begin
  Cache lock (with ttl) <- error (end transaction)
  Save to DB // skip
  Save to cache // skip
Commit
Delete cache lock by ttl?
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  Cache lock (with ttl)
  <- error (end transaction and locks)
  Save to DB // skip
  Save to cache // skip
Commit
Delete cache lock
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB  <- error (Rollback db, end row locks)
  Save to cache // skip
Commit
Delete cache lock
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB
  <- exit (Rollback db, end row lock)
  Save to cache // skip
Commit
Delete cache lock
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB
  Save to cache <- exit (Rollback db, end row lock)
Commit (end row lock)
DB+, Cache-, Future reads and updates blocked
Must rollback cache!
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB
  Save to cache
  <- exit (DB rollback, but cache invalid)
Commit (end row lock)
DB+, Cache-, Future updates blocked
Must rollback cache!
```


### Sleeps


```
Begin
  <- sleep
  Cache lock (with ttl) // skip
  Save to DB // skip
  Save to cache // skip
Commit (end row lock)
Delete cache lock
DB+, Cache+, OK
```

```
Begin
  Cache lock (with ttl) <- sleep (1min)
  Save to DB // skip
  Check timer? rollback if sleep long
  Save to cache // skip
Commit
Delete cache lock by ttl?
DB+, Cache+, OK
```

```
Begin
  Cache lock (with ttl)
  <- sleep (1min)
  Save to DB // skip
  Check timer? rollback if sleep long
  Save to cache // skip
Commit
Delete cache lock
DB+, Cache+, OK
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB  <- sleep (1min)
  Check timer? rollback if sleep long
  Save to cache // skip
Commit
Delete cache lock
DB+, Cache+, OK
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB
  <- sleep (1min)
  Check timer? rollback if sleep long
  Save to cache // skip
Commit
Delete cache lock
DB+, Cache+, Future reads and updates blocked
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB
  Save to cache <- sleep (1min)
  Check timer? rollback if sleep long
Commit (end row lock)
DB+, Cache-, WRONG
```

```
Begin
  Cache lock (with ttl) (start row lock for update and read)
  Save to DB
  Save to cache
  Check timer? rollback if sleep long
  <- sleep (1min)
Commit (end row lock)
DB-, Cache+, WRONG
Must rollback cache!
```
