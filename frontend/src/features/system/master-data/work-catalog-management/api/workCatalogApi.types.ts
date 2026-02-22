export interface ApiListResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export interface ApiItemResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}
