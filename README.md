# CRUD GENERATOR - FOR SLIM 4 - API SKELETON

This package provide a command to generate RESTful endpoints, to manage any simple entity/table.

Given an resource, like a table in MySQL, autogenerate a simple CRUD endpoints.

For example, if you have a table with the name 'user', the script generate the new endpoints on routes `/user`.

Following the previous example, the command generate 5 (five) new endpoints like:

- Get All Users: `GET /user`
- Get One User: `GET /user/{id}`
- Create User: `POST /user`
- Update User: `PUT /user/{id}`
- Delete User: `DELETE /user/{id}`

So, the script generate a real example with all files and directories: Controller, Services, Repository, etc, etc, that allow you to manage the new resource using as RESTful API.

Furthermore, the script make a file with PHPUnit tests, for each new endpoint generated.


## HOW TO USE:

```bash
$ ./console api:generate:endpoints [table-name]
OK - Generated endpoints for entity: [table-name]
```

**This package is for exclusive use of this [Slim 4 - Api Skeleton](https://github.com/maurobonfietti/slim4-api-skeleton).**

*Work In Progress...*
**Work In Progress...**
***Work In Progress :-)***
