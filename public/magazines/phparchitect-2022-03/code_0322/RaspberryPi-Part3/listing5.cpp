int main()
{
    // Open connection to I2C bus
    int fileDescriptor;
    char bus[] = "/dev/i2c-1";

    if((fileDescriptor = open(bus, O_RDWR)) < 0)
    {
      	printf("Failed to open the bus. \n");
      	exit(1);
    }
		...