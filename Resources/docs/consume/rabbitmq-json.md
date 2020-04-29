# Source RabbitMQ json

This source can collect data from a RabbitMQ queue containing raw json (i.e. not a serialized enveloper like Messenger usuallly handles).

## Configuration reference

```
framework:
    messenger:
        transports:
            consumer:
                dsn: "vdm+amqp://guest:guest@rabbitmq:5672/%2f/request"
```

Configuration | Description
--- | ---
dsn | the queue you want to collect from (and its credentials)