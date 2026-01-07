-   Prefer grouping the code in layers: Frontend, Services, Models.

    -   Frontend: Livewire components, API Controllers; here we handle the user interaction, validation, authentication, and authorization. we rely on the services layer to perform the necessary tasks, we can also use directy the models for simple things.
    -   Services: Business logic, API calls, Data processing, Validation, the services should be stateless and only rely on the models for data access, they are agnostic to the context in which they are used so they cannot depend of things like the current user, request, etc.
    -   Models: Data representation, Database interactions, query scopes.

-   We develop the aplication in a containers, to execute commands inside the container we use the "cr" alias, defined in the .terminal_helpers.sh file. Example:

```bash
cr app php artisan make:model User
```

-   We use the UI components from maryUI, that uses daisyUI for the styling. For docuemntation of the components see the source code in src/lib/mary/src/View/Components
