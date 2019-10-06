# SKEL API SLIM PHP CRUD GENERATOR

This package provide a command to generate RESTful endpoints, to manage any simple entity/table.

Given an resource, like a entity or table in MySQL, autogenerate a simple CRUD endpoints.

For example, if you have an 'user' table, the script generate 5 (five) new endpoints on the routes "/user".

#### NEW ENDPOINTS:

##### (Following the previous example)

- Get All Users: `GET /user`
- Get One User: `GET /user/{id}`
- Create User: `POST /user`
- Update User: `PUT /user/{id}`
- Delete User: `DELETE /user/{id}`

So, the script generate a real example with all files and directories: Controller, Services, Repository, etc, that allowing to manage the new resource.
Also, the script create PHPUnit tests, for each new endpoint.


## HOW TO USE:

```bash
$ ./console api:generate:endpoints {table-name}
Starting!
Generate Endpoints For New Entity: user
Script Finish ;-)
```

*Work In Progress...*
**Work In Progress...**
***Work In Progress :-)***

**For exclusive use of this** [Skeleton REST API Slim PHP](https://github.com/maurobonfietti/skel-api-slim-php).
