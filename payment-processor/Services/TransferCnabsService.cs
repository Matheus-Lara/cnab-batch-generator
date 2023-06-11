

public class TransferCnabsService
{
	public void MoveFileByName(string fileName)
	{
		var file = Directory.GetFiles("./cnab_files_transfer").Where(file => file == "./cnab_files_transfer/" + fileName).FirstOrDefault();

		if (file == null)
		{
			Console.WriteLine($"Arquivo não encontrado: {fileName}");
			return;
		}
		Console.WriteLine($"Path combine");

		var destination = Path.Combine("./cnab_received_files", fileName);
		File.Move(file, destination);
	}
}
