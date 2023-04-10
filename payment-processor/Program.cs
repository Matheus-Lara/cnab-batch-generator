var builder = WebApplication.CreateBuilder(args);

builder.Services.AddControllers();
builder.Services.AddEndpointsApiExplorer();
builder.Services.AddSwaggerGen();

var app = builder.Build();

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

app.MapGet("/health-check", () => "API Health!");

app.MapPost("/transfer-cnabs", () => {
	TransferCnabsService transferCnabsService = new TransferCnabsService();
	transferCnabsService.MoveFiles();
	return Results.NoContent();
});

app.MapPost("/process-payment", (PaymentDto payment) => {
	ProcessPaymentService processPaymentService = new ProcessPaymentService();
	processPaymentService.ProcessPayment(payment);

	if (processPaymentService.HasError())
	{
		return Results.BadRequest(processPaymentService.errors);
	}

	return Results.NoContent();
});

app.Run();