## How to contribute

There are a few guidelines that we need contributors to follow so that we can have a chance of keeping on top of things.

### Getting Started

Fork the repository

### With docker:

First install all the dependencies;

```bash
docker run --rm -v "${PWD}:/app" -w /app composer:1.10.12 install
```

Now run the container
```bash
docker-compose -f docker-compose.dev.yml up
```

If you leave the defaults env vars value, the API will be available at [http://localhost:8018/cloudcsv_api](http://localhost:8018/cloudcsv_api)

### Submitting Changes

- Push your changes to your fork
- Submit a pull request