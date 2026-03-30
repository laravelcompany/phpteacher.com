private function getJsonEncodedDataFromAccelDataObjects($accelDataObjects)
{
    $measurements = array();

    foreach ($accelDataObjects as $accel)
    {
        $measurements[] = 
            array(
                'axis' => 
                    array(
                        'X' => $accel->axis_x,
                        'Y' => $accel->axis_y,
                        'Z' => $accel->axis_z
                    ),
                'dateTime' => $accel->created
            );
    }

    $accelerationData = 
        array(
            'accelerationData' =>
                array(
                    'accelerationMeasurements' => $measurements,
                    'lastMeasurementId' => $accel->id
                )
        );

    return json_encode($accelerationData, JSON_PRETTY_PRINT);
}