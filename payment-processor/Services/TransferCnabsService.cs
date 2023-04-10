

public class TransferCnabsService
{
    public void TransferCnabs()
    {}

	public void MoveFiles()
	{
		var files = Directory.GetFiles("./cnab_files_transfer");

		files = files.Where(file => file != "./cnab_files_transfer/.gitignore").ToArray();

		foreach (var file in files)
		{
			var fileName = Path.GetFileName(file);
			var destination = Path.Combine("./cnab_received_files", fileName);
			File.Move(file, destination);
		}
	}
}
