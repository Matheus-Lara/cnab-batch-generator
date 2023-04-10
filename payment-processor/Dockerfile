FROM mcr.microsoft.com/dotnet/sdk:7.0-jammy AS build

WORKDIR /payment-processor

COPY . .

# Restore as distinct layers
RUN dotnet restore
# Build and publish a release
RUN dotnet publish -c Release -o out

# Build runtime image
FROM mcr.microsoft.com/dotnet/aspnet:7.0
WORKDIR /payment-processor
COPY --from=build /payment-processor/out .
ENTRYPOINT ["dotnet", "payment-processor.dll"]