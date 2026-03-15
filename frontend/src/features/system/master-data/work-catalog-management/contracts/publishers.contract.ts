export interface PublisherDTO {
  id: number;
  name: string;
  code: string;
  address: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  is_active: boolean;
  updated_at: string;
}

export interface PublisherUpsertDTO {
  name: string;
  code: string;
  address: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  is_active: boolean;
}

export interface Publisher {
  id: number;
  name: string;
  code: string;
  address: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  isActive: boolean;
  updatedAt: string;
}

export function publisherFromDto(dto: PublisherDTO): Publisher {
  return {
    id: dto.id,
    name: dto.name,
    code: dto.code,
    address: dto.address ?? null,
    phone: dto.phone ?? null,
    email: dto.email ?? null,
    website: dto.website ?? null,
    isActive: !!dto.is_active,
    updatedAt: dto.updated_at,
  };
}
