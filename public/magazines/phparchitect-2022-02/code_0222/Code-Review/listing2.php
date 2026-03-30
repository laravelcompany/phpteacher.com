Class EmployerService
{
    public function transformToDTO(EmployerEntity $employer): EmployerDTO {}

    public function findInRepository(int $employerId): EmployerEntity {}
}