BEGIN
  CLASS Person(name); NAME name;
  BEGIN
    OUTTEXT("Name of the person is: ");
    OUTNAME(name);
    OUTIMAGE;
  END;

  Person Per1("John");
  Person Per2("Alice");
END;