export type PaginationDTO = {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type ListResponseDTO<T> = {
  items: T[];
  pagination: PaginationDTO;
};
