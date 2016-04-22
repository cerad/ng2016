
Controllers

A controller service is linked to a route via the _controller property
The Symfony kernel calls Controller::__invoke when the route is matched.
Controller::__invoke returns either a Response object or null if a view is defined.

Views

If a controller returns null the the Symfony kernel will send a Kernel::View event.

A view service is linked to a route via the _view property.
View::__invoke($request) is called by the KernelListener on view events.

You can also define _view_format route properties in which case the view service is called based on the route __format value.

Models
A model factory service is linked to a route via the _model property.

A model is created via $modelFactory->create($request) then injected into the request model property.

The model factory will throw exceptions if the model cannot be created.

Access Control

Roles can be linked to a route via the _role property.
The KernelListener will throw an AccessDenied exception if the user does not have the role.

Projects

For the national games, the current project is ???

Could inject into the request object if _project is true.

Might also be injected into controller etc via dependency injection.

More thought required.
