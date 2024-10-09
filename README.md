Symfony Semantic Search Demo Application
========================================

The "Symfony Semantic Search Demo Application" is a reference application created to show how
to develop semantic search following the [Symfony Best Practices][1] based on the Symfony Demo application.

You can also learn about these practices in [the official Symfony Book][5].

Requirements
------------

  * PHP 8.2.0 or higher;
  * PDO-SQLite PHP extension enabled;
  * and the [usual Symfony application requirements][2].
  * [Ollama](https://ollama.com/) (along with `bge-large:335m-en-v1.5-fp16`, `all-minilm:22m`, `snowflake-arctic-embed:335m` and `llama3.2:1b-instruct-q8_0` models)
  * [Docker](https://www.docker.com/)

Usage
-----

Launching `Meilisearch`:

```bash
make up
```

Serving the application:

```bash
symfony serve OR php -S localhost:8000 -t public/
```

Then access the application in your browser at the given URL (<https://localhost:8000> by default).

Makefile
--------

The make recipes can be accessed using: 

```bash
make
```

Meilisearch
-----------

The `meilisearch` service is available at `<http://localhost:7700>`.

A dump is provided to test the query easily without rebuilding the indexes.

The following commands can be used to manage the `meilisearch` service:

```bash
make enable-vector-store
```

If you prefer to test it, the following query can be sent (after pulling the models using `Ollama`) to trigger the embedding generation:

```bash
PATCH http://localhost:7700/indexes/app_dev_posts/settings
Content-Type: application/json
Authorization: Bearer 7d9b594befb76b801dd850fd21bc9409174cfc2af41ca3ceda5681ba81f9

{
    "embedders": {
      "ollama_bge-large": {
        "source": "ollama",
        "url": "http://host.docker.internal:11434/api/embeddings",
        "model": "bge-large:335m-en-v1.5-fp16",
        "dimensions": 1024,
        "documentTemplate": "A movie titled {{doc.title}} whose resumed in {{doc.summary}} and the whole critic is {{doc.content|truncatewords: 100}}, the critic is written by {{doc.author.fullName}}",
        "distribution": {
          "mean": 0.9,
          "sigma": 0.4
        }
      },
      "ollama_mini-lm": {
        "source": "ollama",
        "url": "http://host.docker.internal:11434/api/embeddings",
        "model": "all-minilm:22m",
        "dimensions": 384,
        "documentTemplate": "A movie titled {{doc.title}} whose resumed in {{doc.summary}} and the whole critic is {{doc.content|truncatewords: 100}}, the critic is written by {{doc.author.fullName}}",
        "distribution": {
          "mean": 0.75,
          "sigma": 0.25
        }
      },
      "ollama_snowflake": {
        "source": "ollama",
        "url": "http://host.docker.internal:11434/api/embeddings",
        "model": "snowflake-arctic-embed:335m",
        "dimensions": 1024,
        "documentTemplate": "A movie titled {{doc.title}} whose resumed in {{doc.summary}} and the whole critic is {{doc.content|truncatewords: 100}}, the critic is written by {{doc.author.fullName}}",
        "distribution": {
          "mean": 0.75,
          "sigma": 0.25
        }
      },
      "ollama_llama32": {
        "source": "ollama",
        "url": "http://host.docker.internal:11434/api/embeddings",
        "model": "llama3.2:1b-instruct-q8_0",
        "dimensions": 2048,
        "documentTemplate": "A movie titled {{doc.title}} whose resumed in {{doc.summary}} and the whole critic is {{doc.content|truncatewords: 100}}, the critic is written by {{doc.author.fullName}}",
        "distribution": {
          "mean": 0.9,
          "sigma": 0.5
        }
      }
    }
}
```

PS: The model used can be changed in `BlogSearchComponent::getPosts()` method.
PS II: For more information about "vector search" in Meilisearch, head to [the official documentation](https://www.meilisearch.com/docs/learn/ai_powered_search/getting_started_with_ai_search).

[1]: https://symfony.com/doc/current/best_practices.html
[2]: https://symfony.com/doc/current/setup.html#technical-requirements
[5]: https://symfony.com/book
[6]: https://getcomposer.org/
