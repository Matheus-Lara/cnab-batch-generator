using Confluent.Kafka;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddSingleton<ConsumerConfig>(new ConsumerConfig
{
    BootstrapServers = "kafka:9092", // Endereço e porta do servidor Kafka
    GroupId = "meu-grupo", // Nome do grupo do consumidor
    AutoOffsetReset = AutoOffsetReset.Earliest // Começa a consumir desde o início do tópico
});

builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

var app = builder.Build();

var consumerConfig = app.Services.GetRequiredService<ConsumerConfig>();
using var consumer = new ConsumerBuilder<Ignore, string>(consumerConfig).Build();
consumer.Subscribe("new-cnab-file"); // Nome do tópico a ser consumido

app.UseSwagger();
app.UseSwaggerUI();

app.Use(async (context, next) =>
{
    if (context.Request.Path == "/")
    {
        context.Response.Redirect("/swagger");
    }
    else
    {
        await next();
    }
});


//
app.MapGet("/health-check", () => "API Health!");

app.MapPost("/transfer-cnabs", () => {
	TransferCnabsService transferCnabsService = new TransferCnabsService();
	transferCnabsService.MoveFiles();
	return Results.NoContent();
}).WithOpenApi(operation => new(operation)
{
	Summary = "Transfere os arquivos de um hotfolder para a pasta definitiva",
	Description = "Transfere arquivos"
});

app.MapPost("/process-payment", (PaymentDto payment) => {
	ProcessPaymentService processPaymentService = new ProcessPaymentService();
	processPaymentService.ProcessPayment(payment);

	if (processPaymentService.HasError())
	{
		return Results.BadRequest(processPaymentService.errors);
	}

	return Results.NoContent();
}).WithOpenApi(operation => new(operation)
{
	Summary = "Recebe a matrícula do usuário a realizar o pagamento e o valor do pagamento e realiza o pagamento, excluindo o arquivo de remessa da pasta definitiva",
	Description = "Realiza pagamento"
});

app.MapGet("/consumer-test", () =>
{
    var consumeResult = consumer.Consume(TimeSpan.FromSeconds(1)); // Aguarda por um evento de consumo
    if (consumeResult != null)
    {
        // Lida com a mensagem consumida
        Console.WriteLine($"Mensagem consumida: {consumeResult.Message.Value}");
        consumer.Commit(consumeResult); // Confirma o consumo da mensagem
    }

    return "Consumer Kafka em execução!";
});

app.Run();
