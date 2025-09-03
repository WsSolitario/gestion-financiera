Peticiones de postman para la documentacion y arreglo de problemas

Los ejemplos se generaron con `MODE_APP=private`, donde el campo `registration_token` es obligatorio al registrarse. Para permitir registros sin token cambia a `MODE_APP=public` en el `.env` y ejecuta `php artisan config:clear`.

POST http://localhost:8000/api/auth/login: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 52772
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "199d51d9-f23e-4578-b9f5-23f342b2642e",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "61"
  },
  "Request Body": "{\n  \"email\": \"owner@test.com\",\n  \"password\": \"Password123!\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:50:34 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Login correcto\",\"token\":\"2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb\",\"user\":{\"id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"name\":\"Owner\",\"email\":\"owner@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:48:53+00\",\"updated_at\":\"2025-09-02 02:48:53+00\",\"is_active\":true}}"
}
GET http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 49943
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "7423a9bf-6d38-4794-89b8-9aec0193bd98",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:50:55 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"user\":{\"id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"name\":\"Owner\",\"email\":\"owner@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:48:53+00\",\"updated_at\":\"2025-09-02 02:48:53+00\",\"is_active\":true}}"
}
POST http://localhost:8000/api/groups: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58578
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "1a118b51-2a3e-47c0-aa87-214c9d5931d8",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "57"
  },
  "Request Body": "{\n  \"name\": \"Casa Roomies\",\n  \"description\": \"Gastos A\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:01 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Grupo creado\",\"group\":{\"id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"name\":\"Casa Roomies\",\"description\":\"Gastos A\",\"owner_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"created_at\":\"2025-09-02 02:51:01.318801+00\"}}"
}
POST http://localhost:8000/api/groups: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 53714
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "5e87c3ba-7d3a-4752-93a5-3c8e4d428ce4",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "51"
  },
  "Request Body": "{\n  \"name\": \"Oficina\",\n  \"description\": \"Grupo B\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:06 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Grupo creado\",\"group\":{\"id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"name\":\"Oficina\",\"description\":\"Grupo B\",\"owner_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"created_at\":\"2025-09-02 02:51:06.467574+00\"}}"
}
POST http://localhost:8000/api/invitations: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 56568
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "271480cd-d468-483c-9bef-36056fb04946",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "118"
  },
  "Request Body": "{\n  \"invitee_email\": \"alice@test.com\",\n  \"group_id\": \"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\n  \"expires_in_days\": 14\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:10 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Invitaci\\u00f3n creada\",\"invitation\":{\"id\":\"54a8a8d0-3465-4035-91e3-28196e1947a0\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"group_name\":null,\"inviter_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"invitee_email\":\"alice@test.com\",\"status\":\"pending\",\"expires_at\":\"2025-09-16 02:51:10+00\",\"token\":\"K5HeRPcyUXCWnPxCps7igxU5Lrh4RTIcD0V9EdFtCcgJ4Zt7JlSOtY7k8LUE8avM\"},\"registration_token\":{\"token\":\"SW44YiyFCVGxPgUE9Rv5MUfaNtODP8ugtH3qUvMwLmFpQwbfN3KecBAFMnO2TzcU\",\"expires_at\":\"2025-09-16T02:51:10.325586Z\"}}"
}
POST http://localhost:8000/api/invitations: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 60424
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "0a58a1f5-3307-40b8-99c7-86dbc13cd4fc",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "116"
  },
  "Request Body": "{\n  \"invitee_email\": \"bob@test.com\",\n  \"group_id\": \"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\n  \"expires_in_days\": 14\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:15 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Invitaci\\u00f3n creada\",\"invitation\":{\"id\":\"41e91326-77cf-4140-8e3d-f0aa30f12c90\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"group_name\":null,\"inviter_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"invitee_email\":\"bob@test.com\",\"status\":\"pending\",\"expires_at\":\"2025-09-16 02:51:15+00\",\"token\":\"g9iJk9bmOLHZ4eTZbQGmso4BqznSqReBteX7EmjndQTyEQxchBnJ3P3JeKtHMU4v\"},\"registration_token\":{\"token\":\"6LVJ4R5SPk36JeuuiyVk0jNLckHOiKTtcsot4p0PRrN5CBqZxm6eSZ3tzA28HPGP\",\"expires_at\":\"2025-09-16T02:51:15.478626Z\"}}"
}
POST http://localhost:8000/api/invitations: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 53976
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "69f88da5-204e-4e5d-9580-60d28db8c717",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "118"
  },
  "Request Body": "{\n  \"invitee_email\": \"carol@test.com\",\n  \"group_id\": \"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\n  \"expires_in_days\": 14\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:19 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Invitaci\\u00f3n creada\",\"invitation\":{\"id\":\"0754aa2c-37fc-4f52-800d-30c68f4d84e1\",\"group_id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"group_name\":null,\"inviter_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"invitee_email\":\"carol@test.com\",\"status\":\"pending\",\"expires_at\":\"2025-09-16 02:51:19+00\",\"token\":\"3kUWfcOzZnlaV5L69N525RGYSf15O5tXBzOOy46QK4Vz4S92QoUpei1lDS8GLVqC\"},\"registration_token\":{\"token\":\"uIQweCKpZE8imIMTZC0aDIyuHKHW2OhaTbHQi7BPOMJmFCpM2oqLj44OlmP4dKk4\",\"expires_at\":\"2025-09-16T02:51:19.636856Z\"}}"
}
GET http://localhost:8000/api/invitations/token/K5HeRPcyUXCWnPxCps7igxU5Lrh4RTIcD0V9EdFtCcgJ4Zt7JlSOtY7k8LUE8avM: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 62455
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "7cc16b8c-2484-41ba-9836-4314fa3f3b03",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:24 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"id\":\"54a8a8d0-3465-4035-91e3-28196e1947a0\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"group_name\":\"Casa Roomies\",\"inviter_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"invitee_email\":\"alice@test.com\",\"status\":\"pending\",\"expires_at\":\"2025-09-16 02:51:10+00\"}"
}
GET http://localhost:8000/api/invitations/token/g9iJk9bmOLHZ4eTZbQGmso4BqznSqReBteX7EmjndQTyEQxchBnJ3P3JeKtHMU4v: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 55794
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "1b2ae89a-f421-44fa-ab00-6ce7208761ed",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:31 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"id\":\"41e91326-77cf-4140-8e3d-f0aa30f12c90\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"group_name\":\"Casa Roomies\",\"inviter_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"invitee_email\":\"bob@test.com\",\"status\":\"pending\",\"expires_at\":\"2025-09-16 02:51:15+00\"}"
}
GET http://localhost:8000/api/invitations/token/3kUWfcOzZnlaV5L69N525RGYSf15O5tXBzOOy46QK4Vz4S92QoUpei1lDS8GLVqC: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 56202
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "bc98f43e-aea3-40ae-8154-056085cf197f",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:37 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"id\":\"0754aa2c-37fc-4f52-800d-30c68f4d84e1\",\"group_id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"group_name\":\"Oficina\",\"inviter_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"invitee_email\":\"carol@test.com\",\"status\":\"pending\",\"expires_at\":\"2025-09-16 02:51:19+00\"}"
}
POST http://localhost:8000/api/auth/register: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58307
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "684487e6-1c9d-4e99-84bb-250fda6de3b6",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "220"
  },
  "Request Body": "{\n  \"name\": \"Alice Demo\",\n  \"email\": \"alice@test.com\",\n  \"password\": \"Password123!\",\n  \"password_confirmation\": \"Password123!\",\n  \"registration_token\": \"SW44YiyFCVGxPgUE9Rv5MUfaNtODP8ugtH3qUvMwLmFpQwbfN3KecBAFMnO2TzcU\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:50 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Registro exitoso\",\"token\":\"3|bbUjirKhWYSolrdlglHkIofj4IcpmWxD9CGP81k393ca9444\",\"user\":{\"id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"name\":\"Alice Demo\",\"email\":\"alice@test.com\",\"profile_picture_url\":null,\"phone_number\":null}}"
}
POST http://localhost:8000/api/auth/login: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 62227
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "459788d2-b8f3-4ed0-81df-83090428ced4",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "61"
  },
  "Request Body": "{\n  \"email\": \"alice@test.com\",\n  \"password\": \"Password123!\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:54 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Login correcto\",\"token\":\"4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999\",\"user\":{\"id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"name\":\"Alice Demo\",\"email\":\"alice@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:51:49.799744+00\",\"updated_at\":\"2025-09-02 02:51:49.799744+00\",\"is_active\":true}}"
}
GET http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 60756
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "c9db4813-8265-4e51-8fff-322ec0d09557",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:51:58 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"user\":{\"id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"name\":\"Alice Demo\",\"email\":\"alice@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:51:49.799744+00\",\"updated_at\":\"2025-09-02 02:51:49.799744+00\",\"is_active\":true}}"
}
POST http://localhost:8000/api/invitations/accept: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 57747
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "408e83c8-9630-4c39-9f49-e18e66d6c5ca",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "81"
  },
  "Request Body": "{\n  \"token\": \"K5HeRPcyUXCWnPxCps7igxU5Lrh4RTIcD0V9EdFtCcgJ4Zt7JlSOtY7k8LUE8avM\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:03 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Invitaci\\u00f3n aceptada\"}"
}
POST http://localhost:8000/api/auth/register: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 54329
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "a975bce2-2e47-4aa9-9ee6-0351626615fb",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "216"
  },
  "Request Body": "{\n  \"name\": \"Bob Demo\",\n  \"email\": \"bob@test.com\",\n  \"password\": \"Password123!\",\n  \"password_confirmation\": \"Password123!\",\n  \"registration_token\": \"6LVJ4R5SPk36JeuuiyVk0jNLckHOiKTtcsot4p0PRrN5CBqZxm6eSZ3tzA28HPGP\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:08 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Registro exitoso\",\"token\":\"5|VuxZC9vkSrQ5Zy0d3wUEMWj3qjU2hgqXHgyuZEX0ea44bd55\",\"user\":{\"id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"name\":\"Bob Demo\",\"email\":\"bob@test.com\",\"profile_picture_url\":null,\"phone_number\":null}}"
}
POST http://localhost:8000/api/auth/login: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 55310
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "85bece68-e105-4a19-8285-b7fef836d61d",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "59"
  },
  "Request Body": "{\n  \"email\": \"bob@test.com\",\n  \"password\": \"Password123!\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:12 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Login correcto\",\"token\":\"6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f\",\"user\":{\"id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"name\":\"Bob Demo\",\"email\":\"bob@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:52:08.430172+00\",\"updated_at\":\"2025-09-02 02:52:08.430172+00\",\"is_active\":true}}"
}
GET http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 53150
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "20a87cab-942e-47ad-8565-b6ac350274ed",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:16 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"user\":{\"id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"name\":\"Bob Demo\",\"email\":\"bob@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:52:08.430172+00\",\"updated_at\":\"2025-09-02 02:52:08.430172+00\",\"is_active\":true}}"
}
POST http://localhost:8000/api/invitations/accept: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 56081
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "566056ee-03fe-4d93-89a6-2d3376d7c85c",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "81"
  },
  "Request Body": "{\n  \"token\": \"g9iJk9bmOLHZ4eTZbQGmso4BqznSqReBteX7EmjndQTyEQxchBnJ3P3JeKtHMU4v\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:21 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Invitaci\\u00f3n aceptada\"}"
}
POST http://localhost:8000/api/auth/register: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 50360
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "1a57b54d-ff5f-48c0-96e0-1626f198b9de",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "220"
  },
  "Request Body": "{\n  \"name\": \"Carol Demo\",\n  \"email\": \"carol@test.com\",\n  \"password\": \"Password123!\",\n  \"password_confirmation\": \"Password123!\",\n  \"registration_token\": \"uIQweCKpZE8imIMTZC0aDIyuHKHW2OhaTbHQi7BPOMJmFCpM2oqLj44OlmP4dKk4\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:26 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Registro exitoso\",\"token\":\"7|UtEZEkxTRig50QHjXiQLvTUlIssykOcbI1cJdfFAecc6cef2\",\"user\":{\"id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"name\":\"Carol Demo\",\"email\":\"carol@test.com\",\"profile_picture_url\":null,\"phone_number\":null}}"
}
POST http://localhost:8000/api/auth/login: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 61229
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "19e8497d-ee7c-4e53-8594-7ff8db41215f",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "61"
  },
  "Request Body": "{\n  \"email\": \"carol@test.com\",\n  \"password\": \"Password123!\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:31 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Login correcto\",\"token\":\"8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309\",\"user\":{\"id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"name\":\"Carol Demo\",\"email\":\"carol@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:52:26.05116+00\",\"updated_at\":\"2025-09-02 02:52:26.05116+00\",\"is_active\":true}}"
}
GET http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 60139
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "1ced3f8b-89b2-4991-9bd6-01bdce360151",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:36 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"user\":{\"id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"name\":\"Carol Demo\",\"email\":\"carol@test.com\",\"profile_picture_url\":null,\"phone_number\":null,\"created_at\":\"2025-09-02 02:52:26.05116+00\",\"updated_at\":\"2025-09-02 02:52:26.05116+00\",\"is_active\":true}}"
}
POST http://localhost:8000/api/invitations/accept: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 64594
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "6fce832d-cc5d-40c9-9880-95e9f09f9986",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "81"
  },
  "Request Body": "{\n  \"token\": \"3kUWfcOzZnlaV5L69N525RGYSf15O5tXBzOOy46QK4Vz4S92QoUpei1lDS8GLVqC\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:41 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Invitaci\\u00f3n aceptada\"}"
}
GET http://localhost:8000/api/groups: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 63239
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "3522b47d-493d-40a0-ae07-ffba3f6f25d1",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:51 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"data\":[{\"id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"name\":\"Casa Roomies\",\"description\":\"Gastos A\",\"owner_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"created_at\":\"2025-09-02 02:51:01.318801+00\",\"my_role\":\"member\",\"members_count\":3}]}"
}
GET http://localhost:8000/api/groups: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 50666
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "c91d8243-fa52-45fe-bf23-b41948e1c433",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:55 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"data\":[{\"id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"name\":\"Casa Roomies\",\"description\":\"Gastos A\",\"owner_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"created_at\":\"2025-09-02 02:51:01.318801+00\",\"my_role\":\"member\",\"members_count\":3}]}"
}
GET http://localhost:8000/api/groups: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 61004
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "1a4649ec-0ebc-4f1e-ad0e-c56c5c3abacf",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:52:59 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"data\":[{\"id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"name\":\"Oficina\",\"description\":\"Grupo B\",\"owner_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"created_at\":\"2025-09-02 02:51:06.467574+00\",\"my_role\":\"member\",\"members_count\":2}]}"
}
GET http://localhost:8000/api/groups/9240b1ce-6336-4f99-a7d8-961fc50a8697: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58078
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "1b749957-063a-4341-a4f7-db630265f4f3",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:53:05 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"group\":{\"id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"name\":\"Casa Roomies\",\"description\":\"Gastos A\",\"owner_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"created_at\":\"2025-09-02 02:51:01.318801+00\"},\"members\":[{\"user_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"name\":\"Owner\",\"email\":\"owner@test.com\",\"role\":\"owner\",\"joined_at\":\"2025-09-02 02:51:01+00\"},{\"user_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"name\":\"Alice Demo\",\"email\":\"alice@test.com\",\"role\":\"member\",\"joined_at\":\"2025-09-02 02:52:03+00\"},{\"user_id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"name\":\"Bob Demo\",\"email\":\"bob@test.com\",\"role\":\"member\",\"joined_at\":\"2025-09-02 02:52:21+00\"}],\"my_role\":\"owner\"}"
}
GET http://localhost:8000/api/groups/12c290db-6b45-4c64-9bf9-8fd2909bd467: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 53661
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "c09180c1-89bf-4745-b97f-94f6a2a0aaf1",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:53:14 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"group\":{\"id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"name\":\"Oficina\",\"description\":\"Grupo B\",\"owner_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"created_at\":\"2025-09-02 02:51:06.467574+00\"},\"members\":[{\"user_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"name\":\"Owner\",\"email\":\"owner@test.com\",\"role\":\"owner\",\"joined_at\":\"2025-09-02 02:51:06+00\"},{\"user_id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"name\":\"Carol Demo\",\"email\":\"carol@test.com\",\"role\":\"member\",\"joined_at\":\"2025-09-02 02:52:41+00\"}],\"my_role\":\"owner\"}"
}
GET http://localhost:8000/api/groups/9240b1ce-6336-4f99-a7d8-961fc50a8697/balances: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 49917
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "c1b8a286-6056-4122-9f58-94c9cee276c2",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:53:23 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"as_of\":\"2025-09-02\",\"data\":[{\"user_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"name\":\"Alice Demo\",\"balance\":\"0\"},{\"user_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"name\":\"Owner\",\"balance\":\"0\"},{\"user_id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"name\":\"Bob Demo\",\"balance\":\"0\"}]}"
}
GET http://localhost:8000/api/groups/12c290db-6b45-4c64-9bf9-8fd2909bd467/balances: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 51272
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "803365e3-1a88-4539-a0ac-3a1ee7fce952",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:53:29 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"group_id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"as_of\":\"2025-09-02\",\"data\":[{\"user_id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"name\":\"Carol Demo\",\"balance\":\"0\"},{\"user_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"name\":\"Owner\",\"balance\":\"0\"}]}"
}
POST http://localhost:8000/api/expenses: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 61558
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "fd66a17c-d7c6-4171-84cd-b71c38c3b92c",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "456"
  },
  "Request Body": "{\n  \"description\": \"S\\u00faper semana\",\n  \"total_amount\": 1200.5,\n  \"group_id\": \"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\n  \"expense_date\": \"2025-09-01\",\n  \"has_ticket\": true,\n  \"ticket_image_url\": \"https://example.com/tickets/market.png\",\n  \"participants\": [\n    {\n      \"user_id\": \"6464d4c1-b815-4376-b477-42ce44ec1f67\",\n      \"amount_due\": 600.25\n    },\n    {\n      \"user_id\": \"d40825d4-4ed8-4008-afc9-27267d398129\",\n      \"amount_due\": 600.25\n    }\n  ]\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:53:39 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Gasto creado\",\"expense\":{\"id\":\"a82f296f-ffd6-4bbd-ac96-0d3d40b7002f\",\"description\":\"S\\u00faper semana\",\"total_amount\":\"1200.50\",\"payer_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"ticket_image_url\":\"https:\\/\\/example.com\\/tickets\\/market.png\",\"ocr_status\":\"pending\",\"status\":\"pending\",\"expense_date\":\"2025-09-01\",\"created_at\":\"2025-09-02 02:53:37.794089+00\",\"updated_at\":\"2025-09-02 02:53:37.794089+00\"}}"
}
POST http://localhost:8000/api/expenses/a82f296f-ffd6-4bbd-ac96-0d3d40b7002f/approve: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 55288
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "71fec4ba-6551-41d7-b59a-72bed6615f23",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "0"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:01 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Gasto aprobado\",\"expense\":{\"id\":\"a82f296f-ffd6-4bbd-ac96-0d3d40b7002f\",\"description\":\"S\\u00faper semana\",\"total_amount\":\"1200.50\",\"payer_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"ticket_image_url\":\"https:\\/\\/example.com\\/tickets\\/market.png\",\"ocr_status\":\"completed\",\"status\":\"approved\",\"expense_date\":\"2025-09-01\",\"created_at\":\"2025-09-02 02:53:37.794089+00\",\"updated_at\":\"2025-09-02 02:54:01.920579+00\"}}"
}
POST http://localhost:8000/api/expenses: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 62997
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "e780fe9e-b2b0-4ace-aa10-00ba661c36a9",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "290"
  },
  "Request Body": "{\n  \"description\": \"Taxi personal\",\n  \"total_amount\": 200.0,\n  \"group_id\": \"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\n  \"expense_date\": \"2025-09-01\",\n  \"has_ticket\": false,\n  \"participants\": [\n    {\n      \"user_id\": \"d40825d4-4ed8-4008-afc9-27267d398129\",\n      \"amount_due\": 200.0\n    }\n  ]\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:10 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Gasto creado\",\"expense\":{\"id\":\"9a262579-395a-4888-91fe-b4b4eb4e7bbe\",\"description\":\"Taxi personal\",\"total_amount\":\"200.00\",\"payer_id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"ticket_image_url\":null,\"ocr_status\":\"skipped\",\"status\":\"pending\",\"expense_date\":\"2025-09-01\",\"created_at\":\"2025-09-02 02:54:10.410769+00\",\"updated_at\":\"2025-09-02 02:54:10.410769+00\"}}"
}
POST http://localhost:8000/api/expenses: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 59542
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "147b9c81-eb45-402a-931d-8fa143eed104",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "352"
  },
  "Request Body": "{\n  \"description\": \"Pizza oficina\",\n  \"total_amount\": 450.0,\n  \"group_id\": \"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\n  \"expense_date\": \"2025-09-01\",\n  \"has_ticket\": true,\n  \"ticket_image_url\": \"https://example.com/tickets/pizza.png\",\n  \"participants\": [\n    {\n      \"user_id\": \"5f49e980-a967-4de2-a1c3-88650596c506\",\n      \"amount_due\": 450.0\n    }\n  ]\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:19 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Gasto creado\",\"expense\":{\"id\":\"484534ab-d43b-446a-8ff1-678697595410\",\"description\":\"Pizza oficina\",\"total_amount\":\"450.00\",\"payer_id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"group_id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"ticket_image_url\":\"https:\\/\\/example.com\\/tickets\\/pizza.png\",\"ocr_status\":\"pending\",\"status\":\"pending\",\"expense_date\":\"2025-09-01\",\"created_at\":\"2025-09-02 02:54:19.590495+00\",\"updated_at\":\"2025-09-02 02:54:19.590495+00\"}}"
}
POST http://localhost:8000/api/payments: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58010
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "143020ef-56d6-4ead-af93-d673de13dbb3",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "281"
  },
  "Request Body": "{\n  \"group_id\": \"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\n  \"from_user_id\": \"d40825d4-4ed8-4008-afc9-27267d398129\",\n  \"to_user_id\": \"6464d4c1-b815-4376-b477-42ce44ec1f67\",\n  \"amount\": 600.25,\n  \"note\": \"Transferencia SPEI\",\n  \"evidence_url\": \"https://example.com/evidence/spei.png\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:32 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Payment created\",\"payment\":{\"id\":\"6b1946e7-edbf-42bb-83ca-9d809c5b1f5f\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"amount\":\"600.25\",\"status\":\"pending\",\"payment_date\":null,\"payment_method\":null,\"note\":\"Transferencia SPEI\",\"evidence_url\":\"https:\\/\\/example.com\\/evidence\\/spei.png\",\"from_user_id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"payer_name\":\"Bob Demo\",\"to_user_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"receiver_name\":\"Alice Demo\",\"direction\":\"outgoing\",\"unapplied_amount\":\"0.00\"}}"
}
POST http://localhost:8000/api/payments/6b1946e7-edbf-42bb-83ca-9d809c5b1f5f/approve: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 55830
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "0b6751d3-8861-4a75-85d5-8120fd1401f9",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "0"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:38 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Payment approved\",\"payment\":{\"id\":\"6b1946e7-edbf-42bb-83ca-9d809c5b1f5f\",\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"amount\":\"600.25\",\"status\":\"approved\",\"payment_date\":\"2025-09-02 02:54:38+00\",\"payment_method\":null,\"note\":\"Transferencia SPEI\",\"evidence_url\":\"https:\\/\\/example.com\\/evidence\\/spei.png\",\"from_user_id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"payer_name\":\"Bob Demo\",\"to_user_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"receiver_name\":\"Alice Demo\",\"direction\":\"incoming\",\"unapplied_amount\":\"0.00\"},\"applied\":[{\"expense_id\":\"a82f296f-ffd6-4bbd-ac96-0d3d40b7002f\",\"participant_id\":\"a82f296f-ffd6-4bbd-ac96-0d3d40b7002f\",\"amount\":600.25}]}"
}
POST http://localhost:8000/api/payments: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58302
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "dea8244e-55f2-47c4-8792-6b5019940a41",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "270"
  },
  "Request Body": "{\n  \"group_id\": \"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\n  \"from_user_id\": \"5f49e980-a967-4de2-a1c3-88650596c506\",\n  \"to_user_id\": \"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\n  \"amount\": 300.0,\n  \"note\": \"Efectivo\",\n  \"evidence_url\": \"https://example.com/evidence/cash.jpg\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:43 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Payment created\",\"payment\":{\"id\":\"5f475da1-db7f-4608-844d-a3304bac0453\",\"group_id\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"amount\":\"300.00\",\"status\":\"pending\",\"payment_date\":null,\"payment_method\":null,\"note\":\"Efectivo\",\"evidence_url\":\"https:\\/\\/example.com\\/evidence\\/cash.jpg\",\"from_user_id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"payer_name\":\"Carol Demo\",\"to_user_id\":\"b078c0ee-803e-44f4-a9e3-c80a3bcca963\",\"receiver_name\":\"Owner\",\"direction\":\"outgoing\",\"unapplied_amount\":\"0.00\"}}"
}
POST http://localhost:8000/api/payments/5f475da1-db7f-4608-844d-a3304bac0453/reject: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 52217
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "5e9782d5-d702-414e-b003-db7819aba78c",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "0"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:50 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Payment rejected\"}"
}
GET http://localhost:8000/api/payments/due?group_id=9240b1ce-6336-4f99-a7d8-961fc50a8697: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 52196
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "76fda0e4-ccbf-4e3e-9b49-ed9119bdee34",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:54:57 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"total_due\":\"600.25\",\"by_creditor\":[{\"creditor_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"creditor_name\":\"Alice Demo\",\"total\":\"600.25\"}],\"by_group\":[{\"group_id\":\"9240b1ce-6336-4f99-a7d8-961fc50a8697\",\"group_name\":\"Casa Roomies\",\"total\":\"600.25\"}],\"recent\":[{\"expense_participant_id\":\"3a40b9de-8b39-47af-928d-918d5cea8f6e\",\"expense_id\":\"a82f296f-ffd6-4bbd-ac96-0d3d40b7002f\",\"description\":\"S\\u00faper semana\",\"expense_date\":\"2025-09-01\",\"creditor_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"creditor_name\":\"Alice Demo\",\"amount_due\":\"600.25\",\"linked_payment_id\":null}]}"
}
POST http://localhost:8000/api/recurring-payments: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 57355
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "caa8dbd7-a250-4b97-aedb-8eadfcff4eba",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "194"
  },
  "Request Body": "{\n  \"description\": \"Renta departamento\",\n  \"amount_monthly\": 8000,\n  \"months\": 12,\n  \"shared_with\": [\n    \"6464d4c1-b815-4376-b477-42ce44ec1f67\",\n    \"d40825d4-4ed8-4008-afc9-27267d398129\"\n  ]\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:55:14 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"The title field is required. (and 3 more errors)\",\"errors\":{\"title\":[\"The title field is required.\"],\"start_date\":[\"The start date field is required.\"],\"day_of_month\":[\"The day of month field is required.\"],\"reminder_days_before\":[\"The reminder days before field is required.\"]}}"
}
POST http://localhost:8000/api/recurring-payments: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 50564
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "63d0f67d-fbbf-4cf8-8131-968a91d8f3d4",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "144"
  },
  "Request Body": "{\n  \"description\": \"Internet hogar\",\n  \"amount_monthly\": 600,\n  \"months\": 6,\n  \"shared_with\": [\n    \"6464d4c1-b815-4376-b477-42ce44ec1f67\"\n  ]\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:55:23 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"The title field is required. (and 3 more errors)\",\"errors\":{\"title\":[\"The title field is required.\"],\"start_date\":[\"The start date field is required.\"],\"day_of_month\":[\"The day of month field is required.\"],\"reminder_days_before\":[\"The reminder days before field is required.\"]}}"
}
POST http://localhost:8000/api/recurring-payments: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 61879
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "0469b146-2a11-448a-8a96-3d8ebdfd29fe",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "152"
  },
  "Request Body": "{\n  \"description\": \"Mantenimiento oficina\",\n  \"amount_monthly\": 1200,\n  \"months\": 3,\n  \"shared_with\": [\n    \"b078c0ee-803e-44f4-a9e3-c80a3bcca963\"\n  ]\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:55:28 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"The title field is required. (and 3 more errors)\",\"errors\":{\"title\":[\"The title field is required.\"],\"start_date\":[\"The start date field is required.\"],\"day_of_month\":[\"The day of month field is required.\"],\"reminder_days_before\":[\"The reminder days before field is required.\"]}}"
}
GET http://localhost:8000/api/recurring-payments: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 50162
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "d6b238d2-c59a-4e28-8915-621c42fdcb3c",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:55:33 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "[]"
}
GET http://localhost:8000/api/dashboard/summary?groupId={groupA_id}&startDate=2025-09-01&endDate=2025-09-01: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 50164
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "4e9e8c9e-b63c-4e23-9755-25aa86100c83",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:55:43 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "The console only shows response bodies smaller than 10 KB inline. To view the complete body, inspect it by clicking Open."
}
GET http://localhost:8000/api/reports?groupId=12c290db-6b45-4c64-9bf9-8fd2909bd467&granularity=month&paymentStatus=any: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58374
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 2|gz7hBGu4R7PnpbrBdNNyPSXagc2g7IGUoAIvEeEu71fe0ffb",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "6865a994-b0cb-4485-a3ec-55400902f9b1",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:55:51 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"filters\":{\"groupId\":\"12c290db-6b45-4c64-9bf9-8fd2909bd467\",\"startDate\":\"2025-08-03\",\"endDate\":\"2025-09-02\",\"granularity\":\"month\",\"paymentStatus\":\"any\"},\"totals\":{\"expenses\":{\"paid_by_you\":\"0.00\",\"your_share\":\"0.00\",\"owed_to_you\":\"0.00\"},\"payments\":{\"incoming\":\"0.00\",\"outgoing\":\"0.00\",\"net\":\"0.00\"}},\"timeseries\":{\"expenses_paid_by_you\":[],\"your_share\":[],\"payments_incoming\":[],\"payments_outgoing\":[]},\"breakdown\":{\"by_group\":{\"expenses_paid_by_you\":[],\"your_share\":[],\"payments_incoming\":[],\"payments_outgoing\":[]},\"by_counterparty\":{\"outgoing_top5\":[],\"incoming_top5\":[]}}}"
}
POST http://localhost:8000/api/notifications/register-device: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58390
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "32244b47-e451-4635-9a90-eb14aea8f84a",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "94"
  },
  "Request Body": "{\n  \"device_token\": \"web-6464d4c1-b815-4376-b477-42ce44ec1f67-token\",\n  \"device_type\": \"web\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:56:02 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Dispositivo registrado\",\"device\":{\"id\":\"21d869db-4121-4713-8eb1-928f8ebdeaad\",\"user_id\":\"6464d4c1-b815-4376-b477-42ce44ec1f67\",\"device_token\":\"web-6464d4c1-b815-4376-b477-42ce44ec1f67-token\",\"device_type\":\"web\"},\"stats\":{\"total_devices_for_user\":1}}"
}
POST http://localhost:8000/api/notifications/register-device: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 49916
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "6f8c8b90-4429-46c1-bc79-0221f2d73d6b",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "102"
  },
  "Request Body": "{\n  \"device_token\": \"android-d40825d4-4ed8-4008-afc9-27267d398129-token\",\n  \"device_type\": \"android\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:56:10 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Dispositivo registrado\",\"device\":{\"id\":\"43ef54b6-112e-4990-9e58-c69144e765e8\",\"user_id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"device_token\":\"android-d40825d4-4ed8-4008-afc9-27267d398129-token\",\"device_type\":\"android\"},\"stats\":{\"total_devices_for_user\":1}}"
}
POST http://localhost:8000/api/notifications/register-device: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 61378
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "f81dba37-d1a2-4c15-b6f6-025ca283d45c",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "94"
  },
  "Request Body": "{\n  \"device_token\": \"ios-5f49e980-a967-4de2-a1c3-88650596c506-token\",\n  \"device_type\": \"ios\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:56:22 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Dispositivo registrado\",\"device\":{\"id\":\"09d99178-9c29-4afb-9ccc-65eecac27b09\",\"user_id\":\"5f49e980-a967-4de2-a1c3-88650596c506\",\"device_token\":\"ios-5f49e980-a967-4de2-a1c3-88650596c506-token\",\"device_type\":\"ios\"},\"stats\":{\"total_devices_for_user\":1}}"
}
PUT http://localhost:8000/api/users/me/password: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 53622
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "84ca6476-e100-44da-ad72-ce0ac3f7ac10",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "111"
  },
  "Request Body": "{\n  \"current_password\": \"Password123!\",\n  \"password\": \"NewPass123!\",\n  \"password_confirmation\": \"NewPass123!\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:56:33 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Contrase\\u00f1a actualizada\"}"
}
POST http://localhost:8000/api/auth/logout: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 57212
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "1b302c23-a993-4ddd-bc3e-963fa73b8de6",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "0"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:56:40 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Sesi\\u00f3n cerrada\"}"
}
DELETE http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 58488
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "68af05b1-af4d-4fd1-a315-17b2c0b0b63e",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive"
  },
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:57:19 GMT",
    "access-control-allow-origin": "*"
  }
}
PUT http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 64286
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 4|8WnQG8sHpN5YYsTyJnvEKDhRhpHmQEWU5Y6eriKQa4b61999",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "6e092315-b626-4bd3-82f1-6b120e8359fe",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "33"
  },
  "Request Body": "{\n  \"name\": \"Alice Demo (edit)\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:57:49 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Unauthenticated.\"}"
}
PUT http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 59544
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 6|3cO6Vuj1LoBlXTHr6unyPZJjV0u5oGtp5fLKsiy50b03346f",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "669672b5-f0b3-4d16-aee6-20af919f0d35",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "40"
  },
  "Request Body": "{\n  \"phone_number\": \"+52 555 111 2222\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:57:55 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Perfil actualizado\",\"user\":{\"id\":\"d40825d4-4ed8-4008-afc9-27267d398129\",\"name\":\"Bob Demo\",\"email\":\"bob@test.com\",\"profile_picture_url\":null,\"phone_number\":\"+52 555 111 2222\",\"created_at\":\"2025-09-02 02:52:08.430172+00\",\"updated_at\":\"2025-09-02 02:57:55.552662+00\",\"is_active\":true}}"
}
PUT http://localhost:8000/api/users/me: {
  "Network": {
    "addresses": {
      "local": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 50842
      },
      "remote": {
        "address": "127.0.0.1",
        "family": "IPv4",
        "port": 8000
      }
    }
  },
  "Request Headers": {
    "accept": "application/json",
    "content-type": "application/json",
    "authorization": "Bearer 8|K0nZU0OdBbpyjaLC1PnzblIyEa7w22jm7eftGnM04243e309",
    "user-agent": "PostmanRuntime/7.45.0",
    "postman-token": "9ad39afe-77e7-4704-a212-ddf585203a74",
    "host": "localhost:8000",
    "accept-encoding": "gzip, deflate, br",
    "connection": "keep-alive",
    "content-length": "62"
  },
  "Request Body": "{\n  \"profile_picture_url\": \"https://example.com/avatar3.png\"\n}",
  "Response Headers": {
    "host": "localhost:8000",
    "connection": "close",
    "x-powered-by": "PHP/8.2.12",
    "cache-control": "no-cache, private",
    "date": "Tue, 02 Sep 2025 02:58:01 GMT",
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Response Body": "{\"message\":\"Unauthenticated.\"}"
}
