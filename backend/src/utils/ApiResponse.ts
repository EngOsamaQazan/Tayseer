export class ApiResponse {
  constructor(
    public statusCode: number,
    public data: any = null,
    public message: string = 'Success',
    public success: boolean = true
  ) {}

  static success(data: any, message = 'Success', statusCode = 200) {
    return new ApiResponse(statusCode, data, message, true);
  }

  static error(message: string, statusCode = 500, data: any = null) {
    return new ApiResponse(statusCode, data, message, false);
  }
}