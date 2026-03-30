void getAccelerometerData(
                          int& fileDescriptor,
                          float& xAxis,
                          float& yAxis,
                          float& zAxis
                          ) {
    // Get I2C device, MMA8452Q I2C address is 0x1D(29)
    ioctl(fileDescriptor, I2C_SLAVE, 0x1D);

    // Select mode register(0x2A)
    // Standby mode(0x00)
    char config[2] = {0};
    config[0] = 0x2A;
    config[1] = 0x00;

    write(fileDescriptor, config, 2);

    // Select mode register(0x2A)
    // Active mode(0x01)
    config[0] = 0x2A;
    config[1] = 0x01;
    write(fileDescriptor, config, 2);

    // Select XYZ data configuration register(0x0E)
    // Set range to +/- 2g(0x00)
    config[0] = 0x0E;
    config[1] = 0x00;
    write(fileDescriptor, config, 2);

    usleep(5000); // 5 ms
		...