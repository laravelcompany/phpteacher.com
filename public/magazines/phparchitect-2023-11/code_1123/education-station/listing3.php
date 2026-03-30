#include <iostream>
#include <string>

class Person {
  private:
    std::string name;
  public:
    // Constructor
    Person(const std::string& aName) : name(aName) {}

    // Method to print the name
    void printName() const {
      std::cout << "Name is: " << name << std::endl;
    }
};

int main() {
  Person per1("John");
  Person per2("Alice");
  per1.printName();
  per2.printName();

  return 0;
}