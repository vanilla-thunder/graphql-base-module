Exceptions and Errors
=====================

When error occur in GraphQL, the server adds an ``errors`` entry in the response. Here is an example

.. code-block:: json

    {
        "errors": [
            {
                "message": "Wished price was not found by id: some-id",
                "locations": [
                    {
                        "line": 2,
                        "column": 3
                    }
                ],
                "path": [
                    "wishedPrice"
                ]
            }
        ]
    }

You can read more about `GraphQL errors in the official spec <http://spec.graphql.org/June2018/#sec-Errors>`_.

In most cases the HTTP status code will also reflect the error condition. So for example it will be ``404`` in case something was not found.

.. toctree::
        :titlesonly:
        :maxdepth: 1
        :glob:

        Exceptions
        Partially Successful
