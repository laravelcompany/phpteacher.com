Object subclass: Person [
  Person class >> new: name [
    | instance |
    instance := self new.
    instance initialize: name.
    ^instance
  ]

  | name |

  Person >> initialize: aName [
    name := aName.
  ]

  Person >> printName [
    Transcript show: 'Name of the person is: ', name; cr.
  ]
]

| per1 per2 |
per1 := Person new: 'John'.
per2 := Person new: 'Alice'.

per1 printName.
per2 printName.