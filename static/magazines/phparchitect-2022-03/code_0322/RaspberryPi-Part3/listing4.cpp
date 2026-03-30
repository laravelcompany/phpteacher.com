...
// Read 7 bytes of data
// status, xAccl msb, xAccl lsb, yAccl msb,
//   yAccl lsb, zAccl msb, zAccl lsb
char data[7] = {0};

if(read(fileDescriptor, data, 7) != 7)
{
    printf("Error : Input/Output error \n");
}
else
{
    // Convert the data to 12-bits
    int xAccl = ((data[1] * 256) + data[2]) / 16;
    if(xAccl > 2047)
    {
        xAccl -= 4096;
    }

    int yAccl = ((data[3] * 256) + data[4]) / 16;
    if(yAccl > 2047)
    {
        yAccl -= 4096;
    }

    int zAccl = ((data[5] * 256) + data[6]) / 16;
    if(zAccl > 2047)
    {
        zAccl -= 4096;
    }

    int scale = 2;

    xAxis = (float) xAccl / (float)(1<<11) * (float)(scale);
    yAxis = (float) yAccl / (float)(1<<11) * (float)(scale);
    zAxis = (float) zAccl / (float)(1<<11) * (float)(scale);

    // Display to stdout
    clearScreen();
    printf("G-Force in X-Axis : %f \n", xAxis);
    printf("G-Force in Y-Axis : %f \n", yAxis);
    printf("G-Force in Z-Axis : %f \n", zAxis);
}