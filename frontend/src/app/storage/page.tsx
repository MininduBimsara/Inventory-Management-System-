"use client";

import { AppShell } from "@/components/AppShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiRequest } from "@/lib/api";
import { toErrorMessage } from "@/lib/format";
import { useCallback, useEffect, useState } from "react";

type Cupboard = {
  id: number;
  name: string;
  code: string;
  location?: string | null;
};
type Place = {
  id: number;
  name: string;
  code: string;
  cupboard_id: number;
  cupboard?: { name: string };
};

type CollectionResponse<T> = {
  data: T[];
};

export default function StoragePage(): React.ReactElement {
  const [cupboards, setCupboards] = useState<Cupboard[]>([]);
  const [places, setPlaces] = useState<Place[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [cupboardName, setCupboardName] = useState("Main Cupboard");
  const [cupboardCode, setCupboardCode] = useState("CP-01");

  const [placeCupboardId, setPlaceCupboardId] = useState<number>(0);
  const [placeName, setPlaceName] = useState("Shelf A");
  const [placeCode, setPlaceCode] = useState("A1");

  const loadData = useCallback(async (): Promise<void> => {
    try {
      const [cupboardsResponse, placesResponse] = await Promise.all([
        apiRequest<CollectionResponse<Cupboard>>("/cupboards"),
        apiRequest<CollectionResponse<Place>>("/places"),
      ]);

      setCupboards(cupboardsResponse.data);
      setPlaces(placesResponse.data);

      if (cupboardsResponse.data.length > 0 && placeCupboardId === 0) {
        setPlaceCupboardId(cupboardsResponse.data[0].id);
      }
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  }, [placeCupboardId]);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void loadData();
  }, [loadData]);

  const handleCupboardCreate = async (
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    try {
      await apiRequest("/cupboards", {
        method: "POST",
        body: JSON.stringify({ name: cupboardName, code: cupboardCode }),
      });
      setMessage("Cupboard created.");
      await loadData();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  const handlePlaceCreate = async (
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    try {
      await apiRequest("/places", {
        method: "POST",
        body: JSON.stringify({
          cupboard_id: placeCupboardId,
          name: placeName,
          code: placeCode,
        }),
      });
      setMessage("Place created.");
      await loadData();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  return (
    <RequireAuth>
      <AppShell
        title="Cupboard and Place Management"
        subtitle="Manage physical storage hierarchy"
      >
        {message ? <p className="success-text">{message}</p> : null}
        {error ? <p className="error-text">{error}</p> : null}

        <div className="split-grid">
          <form
            className="panel-card form-grid"
            onSubmit={handleCupboardCreate}
          >
            <h3>Create Cupboard</h3>
            <input
              value={cupboardName}
              onChange={(event) => setCupboardName(event.target.value)}
              required
            />
            <input
              value={cupboardCode}
              onChange={(event) => setCupboardCode(event.target.value)}
              required
            />
            <button type="submit">Create Cupboard</button>
          </form>

          <form className="panel-card form-grid" onSubmit={handlePlaceCreate}>
            <h3>Create Place</h3>
            <select
              value={placeCupboardId}
              onChange={(event) =>
                setPlaceCupboardId(Number(event.target.value))
              }
              required
            >
              {cupboards.map((cupboard) => (
                <option key={cupboard.id} value={cupboard.id}>
                  {cupboard.name}
                </option>
              ))}
            </select>
            <input
              value={placeName}
              onChange={(event) => setPlaceName(event.target.value)}
              required
            />
            <input
              value={placeCode}
              onChange={(event) => setPlaceCode(event.target.value)}
              required
            />
            <button type="submit">Create Place</button>
          </form>
        </div>

        <div className="split-grid">
          <div className="panel-card">
            <h3>Cupboards</h3>
            <ul className="list-grid">
              {cupboards.map((cupboard) => (
                <li key={cupboard.id}>
                  <strong>{cupboard.name}</strong> <span>{cupboard.code}</span>
                </li>
              ))}
            </ul>
          </div>

          <div className="panel-card">
            <h3>Places</h3>
            <ul className="list-grid">
              {places.map((place) => (
                <li key={place.id}>
                  <strong>{place.name}</strong>
                  <span>
                    {place.code} in{" "}
                    {place.cupboard?.name ?? `Cupboard ${place.cupboard_id}`}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </AppShell>
    </RequireAuth>
  );
}
