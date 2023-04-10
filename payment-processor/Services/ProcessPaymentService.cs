
public class ProcessPaymentService
{
	public List<string> errors;

	public bool HasError()
	{
		return errors.Count > 0;
	}


	public void ProcessPayment(PaymentDto payment)
	{
		errors = new List<string>();
		if (payment.Matricula == 0)
		{
			errors.Add("Matricula não pode ser 0");
		}

		if (payment.Valor == 0)
		{
			errors.Add("Valor não pode ser 0");
		}

		if (HasError())
		{
			return;
		}

		var fileName = $"cliente-{payment.Matricula}.txt";
		var filePath = Path.Combine("./cnab_received_files", fileName);

		if (!File.Exists(filePath))
		{
			errors.Add("Não existe cobrança em aberto para esta matricula ou o pagamento já foi processado.");
			return;
		}

		var logMessage = $"Recebendo da Matricula: {payment.Matricula} Valor: {payment.Valor}";
		Console.WriteLine(logMessage);

		//TODO: Ponto de entrada para processamento real do pagamento

		File.Delete(filePath);
	}
}
